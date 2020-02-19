<?php

namespace ZfcUser\Form;

use ZfcUser\Options\AuthenticationOptionsInterface;

class ChangePassword extends ProvidesEventsForm
{
    /**
     * @var AuthenticationOptionsInterface
     */
    protected $authOptions;

    public function __construct($name, AuthenticationOptionsInterface $options)
    {
        $this->setAuthenticationOptions($options);

        parent::__construct($name);

        $this->add([
            'name'       => 'identity',
            'options'    => [
                'label' => '',
            ],
            'attributes' => [
                'id'   => 'identity',
                'type' => 'hidden',
            ],
        ]);

        $this->add([
            'name'       => 'credential',
            'type'       => 'password',
            'options'    => [
                'label' => 'Current Password',
            ],
            'attributes' => [
                'id'   => 'credential',
                'type' => 'password',
            ],
        ]);

        $this->add([
            'name'       => 'newCredential',
            'options'    => [
                'label' => 'New Password',
            ],
            'attributes' => [
                'id'   => 'newCredential',
                'type' => 'password',
            ],
        ]);

        $this->add([
            'name'       => 'newCredentialVerify',
            'type'       => 'password',
            'options'    => [
                'label' => 'Verify New Password',
            ],
            'attributes' => [
                'id'   => 'newCredentialVerify',
                'type' => 'password',
            ],
        ]);

        $this->add([
            'name'       => 'submit',
            'attributes' => [
                'value' => 'Submit',
                'type'  => 'submit'
            ],
        ]);
    }

    /**
     * Set Authentication-related Options
     *
     * @param AuthenticationOptionsInterface $authOptions
     * @return ChangePassword
     */
    public function setAuthenticationOptions(AuthenticationOptionsInterface $authOptions)
    {
        $this->authOptions = $authOptions;

        return $this;
    }

    /**
     * Get Authentication-related Options
     *
     * @return AuthenticationOptionsInterface
     */
    public function getAuthenticationOptions()
    {
        return $this->authOptions;
    }
}
