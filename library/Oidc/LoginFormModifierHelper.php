<?php

namespace Icinga\Module\Oidc;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Module\Oidc\Common\Database;
use Icinga\Module\Oidc\Model\Provider;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\StyleWithNonce;
use ipl\Web\Url;

class LoginFormModifierHelper
{

    public static function init()
    {
        if(! empty($_GET['redirect'])){
            setcookie("oidc-redirect", $_GET['redirect'], time() + 300, str_replace("//","/",Icinga::app()->getRequest()->getBasePath()."/"));
        }else{
            setcookie("oidc-redirect", "", time() -3600, str_replace("//","/",Icinga::app()->getRequest()->getBasePath()."/"));

        }
        $relogin = Config::module('oidc')->get("experimental","relogin", "0") === "1";

        if ($relogin) {
            if(strpos(Icinga::app()->getRequest()->getUrl(),"oidc-logout") === false){

                if( !empty($_COOKIE['oidc-internalurl'] ) ){
                    $redirect = $_COOKIE['oidc-internalurl'];
                    Icinga::app()->getRequest()->getResponse()->redirectAndExit($redirect);
                }
            }
        }





    }
    public static function renderAfterForm()
    {

        $providers = Provider::on(Database::get())->filter(Filter::equal('enabled', 'y'));
        $fileHelper = new FileHelper(Module::get('oidc')->getConfigDir() . DIRECTORY_SEPARATOR . "files");
        $allProviders = Html::tag("div", ["class" => "icinga-module module-oidc"]);
        foreach ($providers as $provider) {
            $div = Html::tag("div", ['class' => 'oidc-button']);
            $buttonColorStyle = (new StyleWithNonce());
            $buttonColorStyle->addFor($div, ['background-color' => $provider->buttoncolor, 'color' => $provider->textcolor]);

            $file = $fileHelper->getFile($provider->logo);
            if ($file != false) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                if($extension === "svg"){
                    $extension .= "+xml";
                }
                $left = Html::tag("div", ['class' => 'left-placeholder']);
                $right = Html::tag("div", ['class' => 'right-placeholder']);
                $imgContent = 'data:image/' . $extension . ';base64, ' . base64_encode(file_get_contents($file['realPath']));
                $img = Html::tag("img", ['class' => 'logo-size', 'src' => $imgContent]);
                $left->add($img);
                $a = Html::tag("a", ['class'=>'button-content-align', 'href' => Url::fromPath("oidc/authentication/realm", ['name' => $provider->name]), 'target' => '_self'], $provider->caption);

                $right->add($a);
                $div->add($left);
                $div->add($right);

            } else {
                $a = Html::tag("a", ['class'=>'button-content-align', 'href' => Url::fromPath("oidc/authentication/realm", ['name' => $provider->name]), 'target' => '_self'], $provider->caption);
                $div->add($a);

            }
            $allProviders->add([$div,$buttonColorStyle]);


        }

        return $allProviders->render();

    }

}
