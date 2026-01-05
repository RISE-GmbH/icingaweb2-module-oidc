<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Oidc\Controllers;


use Icinga\Authentication\User\DomainAwareInterface;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\UserBackendInterface;

use Icinga\User;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Zend_Controller_Action_Exception;

class ImportController extends AuthBackendController
{
    public function init()
    {
        $this->view->title = $this->translate('Users');

        parent::init();
    }

    /**
     * List all users of a single backend
     */
    public function listAction()
    {
        $this->assertPermission('oidc/import');
        $this->createListTabs()->activate('oidc/import/list');
        $backendNames = array_map(
            function ($b) {
                return $b->getName();
            },
            $this->loadUserBackends('Icinga\Data\Selectable')
        );
        if (empty($backendNames)) {
            return;
        }

        $this->view->backendSelection = new Form();
        $this->view->backendSelection->setAttrib('class', 'backend-selection icinga-controls');
        $this->view->backendSelection->setUidDisabled();
        $this->view->backendSelection->setMethod('GET');
        $this->view->backendSelection->setTokenDisabled();
        $this->view->backendSelection->addElement(
            'select',
            'backend',
            array(
                'autosubmit'    => true,
                'label'         => $this->translate('User Backend'),
                'multiOptions'  => array_combine($backendNames, $backendNames),
                'value'         => $this->params->get('backend')
            )
        );

        $backend = $this->getUserBackend($this->params->get('backend'));
        if ($backend === null) {
            $this->view->backend = null;
            return;
        }

        $query = $backend->select(array('user_name'));

        $this->view->users = $query;
        $this->view->backend = $backend;

        $this->setupPaginationControl($query);
        $this->setupFilterControl($query);
        $this->setupLimitControl();
        $this->setupSortControl(
            array(
                'user_name'     => $this->translate('Username'),
                'is_active'     => $this->translate('Active'),
                'created_at'    => $this->translate('Created at'),
                'last_modified' => $this->translate('Last modified')
            ),
            $query
        );
    }

    /**
     * Return all user backends implementing the given interface
     *
     * @param   string  $interface      The class path of the interface, or null if no interface check should be made
     *
     * @return  array
     */
    protected function loadUserBackends($interface = null)
    {
        $backends = array();
        foreach ($this->Config('userbackends') as $backendName => $backendConfig) {
            $candidate = UserBackend::create($backendName, $backendConfig);
            if (! $interface || $candidate instanceof $interface) {
                $backends[] = $candidate;
            }
        }

        return $backends;
    }

    /**
     * Return the given user backend or the first match in order
     *
     * @param   string  $name           The name of the backend, or null in case the first match should be returned
     * @param   string  $interface      The interface the backend should implement, no interface check if null
     *
     * @return  UserBackendInterface
     *
     * @throws  Zend_Controller_Action_Exception    In case the given backend name is invalid
     */
    protected function getUserBackend($name = null, $interface = 'Icinga\Data\Selectable')
    {
        $backend = null;
        if ($name !== null) {
            $config = $this->Config('userbackends');
            if (! $config->hasSection($name)) {
                $this->httpNotFound(sprintf($this->translate('Authentication backend "%s" not found'), $name));
            } else {
                $backend = UserBackend::create($name, $config->getSection($name));
                if ($interface && !$backend instanceof $interface) {
                    $interfaceParts = explode('\\', strtolower($interface));
                    throw new Zend_Controller_Action_Exception(
                        sprintf(
                            $this->translate('Authentication backend "%s" is not %s'),
                            $name,
                            array_pop($interfaceParts)
                        ),
                        400
                    );
                }
            }
        } else {
            $backends = $this->loadUserBackends($interface);
            $backend = array_shift($backends);
        }

        return $backend;
    }
    /**
     * Show a user
     */
    public function showAction()
    {
        $this->assertPermission('oidc/import');
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'));


        $user = $backend->select(array(
            'user_name',
            'is_active',
            'created_at',
            'last_modified'
        ))->where('user_name', $userName)->fetchRow();
        if ($user === false) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $userObj = new User($userName);
        if ($backend instanceof DomainAwareInterface) {
            $userObj->setDomain($backend->getDomain());
        }

        $this->setupLimitControl();



        $this->view->user = $user;
        $this->view->backend = $backend;
        $this->createShowTabs($backend->getName(), $userName)->activate('oidc/import/show');

        $this->view->userObj = $userObj;

    }


    /**
     * Create the tabs to display when showing a user
     *
     * @param   string  $backendName
     * @param   string  $userName
     */
    protected function createShowTabs($backendName, $userName)
    {
        $tabs = $this->getTabs();
        $tabs->add(
            'oidc/import/show',
            array(
                'title'     => sprintf($this->translate('Show user %s'), $userName),
                'label'     => $this->translate('User'),
                'url'       => Url::fromPath('oidc/import/show', array('backend' => $backendName, 'user' => $userName))
            )
        );

        return $tabs;
    }

    /**
     * Create the tabs to display when listing users
     */
    protected function createListTabs()
    {
        $tabs = $this->getTabs();


        $tabs->add(
            'oidc/import/list',
            array(
                'title'     => $this->translate('List users of authentication backends'),
                'label'     => $this->translate('Users'),
                'url'       => 'oidc/import/list'
            )
        );



        return $tabs;
    }
}
