<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Oidc\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Hook\AuditHook;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Exception\Http\HttpException;
use Icinga\Module\Oidc\Common\Database;
use Icinga\Module\Oidc\LoginFormModifierHelper;
use Icinga\Module\Oidc\Model\Group;
use Icinga\Module\Oidc\Model\GroupMembership;
use Icinga\Module\Oidc\Model\Provider;
use Icinga\User;
use Icinga\Module\Oidc\Model\User as OidcUser;
use Icinga\Util\StringHelper;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use Jumbojett\OpenIDConnectClient;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends \Icinga\Controllers\AuthenticationController
{
    /**
     * Log into the application
     */
    public function loginAction()
    {

        $this->view->addHelperPath(Icinga::app()->getBaseDir()
            . DIRECTORY_SEPARATOR . "application/views/helpers/");
        $this->view->addScriptPath(Icinga::app()->getBaseDir()
            . DIRECTORY_SEPARATOR . "application/views/scripts/");
        parent::loginAction();
        LoginFormModifierHelper::init();
        $this->view->form=$this->view->form."\n".LoginFormModifierHelper::renderAfterForm();

    }
    public function realmAction(){

        $name = $this->params->getRequired("name");
        $authSuccess=false;
        $provider = Provider::on(Database::get())->filter(Filter::equal('name', $name))->first();
        if($provider === null){
            throw new HttpException(404,"Provider not found");
        }
        if(! $provider->enabled){
            throw new HttpException(405,"Provider not enabled");
        }
        try {
            $oidc = new OpenIDConnectClient($provider->url, $provider->appname, $provider->secret);

        // Register the post-login callback URL

            $scheme= $this->getRequest()->getScheme();
            if($provider->enforce_scheme_https === true || $provider->enforce_scheme_https === 'y'){
                $scheme="https";
            }
            $oidc->setRedirectURL(
                \ipl\Web\Url::fromPath('oidc/authentication/realm',['name'=>$name])
                    ->setIsExternal(true)
                    ->setHost($this->getRequest()->getHttpHost())
                    ->setScheme($scheme)
                    ->getAbsoluteUrl()
            );

            // Register what scopes you need.
            // Initiate the login process at the OP
            if($provider->nooidcgroups){
                $oidc->addScope(['profile','email']);
            }else{
                $oidc->addScope(['profile', 'groups', 'email']);
            }

            $redirect="dashboard";




            if($oidc->authenticate()){
                if( !empty($_COOKIE['oidc-redirect']) ){
                    $redirect = $_COOKIE['oidc-redirect'];
                    setcookie("oidc-redirect", "", time() -3600, "/icingaweb2/");
                }

                $authSuccess=true;
                $_SESSION['id_token'] = $oidc->getIdToken();

                if(isset($provider->azure_groups) && ($provider->azure_groups === true || $provider->azure_groups === 'y')){
                    $claims = $oidc->getIdTokenPayload();
                }else{
                    $claims = $oidc->requestUserInfo();
                }

                $fallback_username= "name";
                if(isset($provider->custom_username) && $provider->custom_username !== ""){
                    $custom_username = $provider->custom_username;
                }else{
                    $custom_username = $fallback_username;

                }
                if(isset($claims->{$custom_username}) && $claims->{$custom_username} !== ""){
                    $username = $claims->{$custom_username};
                }else{
                    $username = $claims->{$fallback_username};
                    Logger::error("Oidc: Using fallback username: ".$fallback_username. " for ".$username);
                }

                $usernameBlacklist = StringHelper::trimSplit($provider->usernameblacklist);
                foreach ($usernameBlacklist as $notAllowedName){
                    if(fnmatch($notAllowedName,$username)){
                        throw new HttpException(401,"Username not allowed for this provider");
                    }
                }
                if(session_status() == PHP_SESSION_ACTIVE){
                    // Icinga wants to handle the session so we destroy ours
                    session_destroy();
                }

            }

        }catch (\Throwable $e){
            Logger::error($e->getMessage());
            Logger::error( $e->getTraceAsString());


        }


        if($authSuccess && $claims != null){
            $oidcUser= OidcUser::on(Database::get())->filter(Filter::equal('name', $username))->filter(Filter::equal('provider_id', $provider->id))->first();
            if($oidcUser === null){
                $oidcUser = new OidcUser();
                $oidcUser->name=$username;
                if(isset($claims->email)){
                    $oidcUser->email=$claims->email;
                }

                $oidcUser->provider_id=$provider->id;
                $oidcUser->lastlogin=(new \DateTime());
                $oidcUser->ctime=(new \DateTime());
                $oidcUser->active='y';
                $oidcUser->save();

            }else{
                if(! $oidcUser->active){

                    throw new HttpException(401,"User not enabled");
                }
                $oidcUser->lastlogin=(new \DateTime());
                if(isset($claims->email)){
                    $oidcUser->email=$claims->email;
                }
                $oidcUser->save();

            }
            $groupsSynclist = StringHelper::trimSplit($provider->syncgroups);
            if($provider->nooidcgroups === "n" || $provider->nooidcgroups === false ){
                if(isset($claims->groups) && is_array($claims->groups)){

                    if($provider->required_groups !== null && $provider->required_groups !== ""){
                        $requiredGroups =  StringHelper::trimSplit($provider->required_groups);
                        $hasRequiredGroup = count($this->filter_by_patterns($claims->groups, $requiredGroups)) > 0;
                        if(!$hasRequiredGroup){
                            throw new HttpException(401,"User has not any required group for this provider");
                        }
                    }


                    if(isset($provider->defaultgroup) && $provider->defaultgroup !== null && $provider->defaultgroup !== ""){
                        $claims->groups[]=$provider->defaultgroup;
                        $groupsSynclist[]=$provider->defaultgroup;
                    }


                    foreach ($claims->groups as $key=>$group){
                        $groupname = $group;
                        $validGroup = false;

                        // todo replace with filter function
                        foreach ($groupsSynclist as $allowedGroup){
                            if(fnmatch($allowedGroup,$groupname)){
                                $validGroup =true;
                                break;
                            }
                        }
                        if(!$validGroup){
                            unset($claims->groups[$key]);
                            continue;
                        }

                        $oidcGroup= Group::on(Database::get())->filter(Filter::equal('name', $groupname))->filter(Filter::equal('provider_id', $provider->id))->first();
                        if($oidcGroup === null){
                            $oidcGroup = new Group();
                            $oidcGroup->name=$groupname;
                            $oidcGroup->provider_id=$provider->id;
                            $oidcGroup->ctime=(new \DateTime());
                            $oidcGroup->save();
                        }

                        $membership = GroupMembership::on(Database::get())->filter(Filter::equal('username', $oidcUser->name))->filter(Filter::equal('group_id', $oidcGroup->id))->first();
                        if($membership === null){
                            $membership = new GroupMembership();
                            $membership->username=$oidcUser->name;
                            $membership->group_id=$oidcGroup->id;
                            $membership->provider_id=$provider->id;
                            $membership->ctime=(new \DateTime());
                            $membership->save();
                        }

                    }
                    $memberships = GroupMembership::on(Database::get())->filter(Filter::equal('username', $oidcUser->name));
                    foreach ($memberships as $membership){
                        $group = Group::on(Database::get())->filter(Filter::equal('id', $membership->group_id))->first();
                        if($group !== null && !in_array($group->name, $claims->groups)){
                            $membership->delete();
                        }
                    }

                }else{
                    if($provider->required_groups !== null && $provider->required_groups !== ""){
                        //since there is an empty group claim we can't satisfy required_groups
                        throw new HttpException(401,"User has not any required group for this provider");
                    }
                }

            }


            $auth = Auth::getInstance();

            if(isset($oidcUser->mapped_local_user) && 	$oidcUser->mapped_local_user !== "" && $oidcUser->mapped_local_user !== null){

                $user = new User($oidcUser->mapped_local_user);

                if(isset($oidcUser->mapped_backend) && 	$oidcUser->mapped_backend !== "" && $oidcUser->mapped_backend !== null){
                    $backendName = $oidcUser->mapped_backend;
                    $backendType = Config::app('authentication')->getSection($backendName)->get('backend');

                }else{
                    $backendName = null;
                    $backendType = null;
                }

                AuditHook::logActivity('login-oidc',sprintf("Oidc-user %s logged in as %s", $oidcUser->name, $oidcUser->mapped_local_user), null, $oidcUser->name);

            }else{
                $backendName = $provider->getUserBackendName();
                $backendType = 'oidc';
                $user = new User($oidcUser->name);
                AuditHook::logActivity('login-oidc',sprintf("Oidc-user %s logged in as %s", $oidcUser->name, $oidcUser->name), null, $oidcUser->name);


            }
            if (! $user->hasDomain()) {
                $user->setDomain(Config::app()->get('authentication', 'default_domain'));
            }

            $user->setAdditional('backend_name', $backendName);
            $user->setAdditional('backend_type', $backendType);
            $user->setAdditional('provider_id', $provider->id);
            $auth->setAuthenticated($user);
            AuthenticationHook::triggerLogin($user);
            $this->redirectNow($redirect);
        }

        $this->redirectNow("oidc/authentication/failed");


    }
    public function filter_by_patterns($array, $patterns) {
        return array_filter($array, function($value) use ($patterns) {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $value)) {
                    return true;
                }
            }
            return false;
        });
    }
    public function failedAction(){
        $this->loginAction();
        $div = Html::tag('div',['class'=>'icinga-module module-oidc']);
        $html = Html::tag('p',['class'=>'oidc-error'],"OIDC: Something went wrong!");
        $div->add($html);
        $this->view->form=$this->view->form.$div;
        $this->_helper->viewRenderer->setRender('authentication/login', null, true);

    }

}
