<?php

namespace ZfcUser\Form;

use Zend\Validator\ValidatorInterface;
use ZfcUser\InputFilter\ProvidesEventsInputFilter;
use ZfcUser\Options\RegistrationOptionsInterface;

class RegisterFilter extends ProvidesEventsInputFilter
{
    /**
     * @var ValidatorInterface
     */
    protected $emailValidator;

    /**
     * @var ValidatorInterface
     */
    protected $usernameValidator;

    /**
     * @var RegistrationOptionsInterface
     */
    protected $options;

    public function __construct(ValidatorInterface $emailValidator, ValidatorInterface $usernameValidator, RegistrationOptionsInterface $options)
    {
        $this->setOptions($options);
        $this->emailValidator    = $emailValidator;
        $this->usernameValidator = $usernameValidator;

        if ($this->getOptions()->getEnableUsername()) {
            $this->add([
                'name'       => 'username',
                'required'   => true,
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'max' => 255,
                        ],
                    ],
                    $this->usernameValidator,
                ],
            ]);
        }

        $this->add([
            'name'       => 'email',
            'required'   => true,
            'validators' => [
                [
                    'name' => 'EmailAddress'
                ],
                $this->emailValidator
            ],
        ]);

        if ($this->getOptions()->getEnableDisplayName()) {
            $this->add([
                'name'       => 'display_name',
                'required'   => true,
                'filters'    => [['name' => 'StringTrim']],
                'validators' => [
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'min' => 3,
                            'max' => 128,
                        ],
                    ],
                ],
            ]);
        }

        $this->add([
            'name'       => 'password',
            'required'   => true,
            'filters'    => [['name' => 'StringTrim']],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 6,
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'passwordVerify',
            'required'   => true,
            'filters'    => [['name' => 'StringTrim']],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 6,
                    ],
                ],
                [
                    'name'    => 'Identical',
                    'options' => [
                        'token' => 'password',
                    ],
                ],
            ],
        ]);
    }

    /**
     * get options
     *
     * @return RegistrationOptionsInterface
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * set options
     *
     * @param RegistrationOptionsInterface $options
     */
    public function setOptions(RegistrationOptionsInterface $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getEmailValidator()
    {
        return $this->emailValidator;
    }

    public function setEmailValidator($emailValidator)
    {
        $this->emailValidator = $emailValidator;

        return $this;
    }

    public function getUsernameValidator()
    {
        return $this->usernameValidator;
    }

    public function setUsernameValidator($usernameValidator)
    {
        $this->usernameValidator = $usernameValidator;

        return $this;
    }
}
