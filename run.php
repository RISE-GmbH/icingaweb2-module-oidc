<?php
/** @var $this \Icinga\Application\Modules\Module */

use Icinga\Application\Icinga;
use Icinga\Module\Oidc\ProvidedHook\LoginButtonHook;

require_once 'vendor/autoload.php';

if (! Icinga::app()->isCli()) {
    LoginButtonHook::register();
}

$this->provideHook('DbMigration', '\\Icinga\\Module\\Oidc\\ProvidedHook\\DbMigration');
$this->provideHook('Authentication', '\\Icinga\\Module\\Oidc\\ProvidedHook\\Authentication', true);
$this->provideUserBackend('oidc', \Icinga\Module\Oidc\Backend\OidcUserBackend::class);
$this->provideUserGroupBackend('oidc', \Icinga\Module\Oidc\Backend\OidcUserGroupBackend::class);
