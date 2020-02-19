<?php

namespace ZfcUser\Form;

use ZfcUser\Options\AuthenticationOptionsInterface;

class ChangeEmail extends ProvidesEventsForm
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
            'name'       => 'newIdentity',
            'options'    => [
                'label' => 'New Email',
            ],
            'attributes' => [
                'id'   => 'newIdentity',
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name'       => 'newIdentityVerify',
            'options'    => [
                'label' => 'Verify New Email',
            ],
            'attributes' => [
                'id'   => 'newIdentityVerify',
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name'       => 'credential',
            'type'       => 'password',
            'options'    => [
                'label' => 'Password',
            ],
            'attributes' => [
                'id'   => 'credential',
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
     * @return ChangeEmail
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
