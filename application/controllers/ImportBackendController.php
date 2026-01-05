<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Oidc\Controllers;


use Icinga\Module\Oidc\BackendTable;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\ButtonLink;

/**
 * Application and module configuration
 */
class ImportBackendController extends CompatController
{

    /**
     * Action for listing user and group backends
     */
    public function indexAction()
    {
        $this->assertPermission('config/oidc');
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('importbackend');

        $this->addControl(
            (new ButtonLink($this->translate('New'), \ipl\Web\Url::fromPath('oidc/import-backend/createuserbackend'), 'plus'))
                ->openInModal()
        );

        $data =[];
        foreach ($this->Config('userbackends')->keys() as $id) {
            $item = ['name'=>$id,'id'=>$id];
            $data[]= (object) $item;
        }

        $this->addContent((new BackendTable())->setData($data));


    }


}
