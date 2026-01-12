<?php

namespace Icinga\Module\Oidc\ProvidedHook;

use Icinga\Module\Loginhooks\Hook\LoginFormModifierHook;
use Icinga\Module\Oidc\LoginFormModifierHelper;


class LoginFormModifier extends LoginFormModifierHook
{

    public function init()
    {
        parent::init();

        LoginFormModifierHelper::init();
    }
    public function renderAfterForm()
    {
        return LoginFormModifierHelper::renderAfterForm();
    }

}
