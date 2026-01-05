<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Oidc\Backend\Form;

use Icinga\Application\Modules\Module;
use Icinga\Module\Oidc\Common\Database;
use Icinga\Module\Oidc\FileHelper;
use Icinga\Module\Oidc\Model\Provider;
use Ramsey\Uuid\Uuid;
use Zend_Validate_Callback;
use Icinga\Web\Form;

/**
 * Form class for adding/modifying user backends of type "external"
 */
class OidcUserBackendForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend_external');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {

        $this->addDescription($this->translate(
            'Do not edit this form, it is managed by the oidc module!'
        ));
        $this->addDescription($this->translate(
            'All changes will be reverted once a provider is created or updated!'
        ));

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Backend Name'),
                'description'   => $this->translate(
                    'The name of this authentication provider that is used to differentiate it from others'
                )
            )
        );
        $callbackValidator = new Zend_Validate_Callback(function ($value) {
            return @preg_match($value, '') !== false;
        });
        $callbackValidator->setMessage(
            $this->translate('"%value%" is not a valid regular expression.'),
            Zend_Validate_Callback::INVALID_VALUE
        );
        $this->addElement(
            'select',
            'provider_id',
            array(
                'required'      => true,
                'label'         => $this->translate('Provider'),
                'description'   => $this->translate(
                    'The Appname referenced in your oidc provider'
                ),
                'multioptions'=>(new Provider())->getAllAsArray('id','name')
            )
        );

        $this->addElement(
            'hidden',
            'backend',
            array(
                'disabled'  => true,
                'value'     => 'oidc'
            )
        );

        //we need to disable the backend otherwise the authchain tries to use it!!!
        $this->addElement(
            'hidden',
            'disabled',
            array(
                'disabled'  => true,
                'value'     => '1'
            )
        );
        return $this;
    }

    /**
     * Validate the configuration by creating a backend and requesting the user count
     *
     * Returns always true as backends of type "external" are just "passive" backends.
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidUserBackend(Form $form)
    {
        return true;
    }


}
