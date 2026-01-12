<?php

namespace Icinga\Module\Oidc\ProvidedHook;

use Icinga\Application\Config;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\User;
use ipl\Web\Url;


class Authentication extends AuthenticationHook
{

    public function onLogin(User $user): void
    {
        return;
    }

    public function onLogout(User $user): void
    {
        $relogin = Config::module('oidc')->get("experimental","relogin", "0") === "1";

        if ($relogin) {
            $oidcProviderID = $user->getAdditional('provider_id');
            setcookie("oidc-internalurl", null, time() - 3600, str_replace("//","/",Icinga::app()->getRequest()->getBasePath()."/"));

            if($oidcProviderID !== null){
                Auth::getInstance()->removeAuthorization();
                $url = Url::fromPath("oidc/authentication/oidc-logout");

                Icinga::app()->getRequest()->getResponse()->setHeader('X-Icinga-Redirect-Http', 'yes');
                Icinga::app()->getRequest()->getResponse()->redirectAndExit($url);
            }
        }

    }
}
