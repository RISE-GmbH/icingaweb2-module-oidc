<?php
/** @var $this \Icinga\Application\Modules\Module */

use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;

require_once 'vendor/autoload.php';
if(!Icinga::app()->isCli()){
    if(Module::exists('loginhooks') && Module::get('loginhooks')->isRegistered()){
        $this->provideHook('loginhooks/LoginFormModifier', \Icinga\Module\Oidc\ProvidedHook\LoginFormModifier::class, true);
    }else{
        $this->addRoute('authentication/login', new Zend_Controller_Router_Route_Static(
            'authentication/login',
            [
                'controller'    => 'authentication',
                'action'        => 'login',
                'module'        => 'oidc'
            ]
        ));
    }


}
$this->provideHook('DbMigration', '\\Icinga\\Module\\Oidc\\ProvidedHook\\DbMigration');
$this->provideHook('Authentication', '\\Icinga\\Module\\Oidc\\ProvidedHook\\Authentication',true);


//$this->provideHook('Oidc\\OidcImplementation', '\Icinga\Module\Oidc\ProvidedHook\Oidc\Default', true);
