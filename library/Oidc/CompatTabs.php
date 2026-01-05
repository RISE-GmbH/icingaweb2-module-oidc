<?php

namespace Icinga\Module\Oidc;

use ipl\Web\Widget\Tabs;

class CompatTabs extends Tabs
{
    public function setTabs($tabs): Tabs
    {
        $this->tabs = $tabs;
        return $this;
    }
}