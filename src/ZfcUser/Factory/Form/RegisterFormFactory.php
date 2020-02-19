<?php

namespace ZfcUser\Factory\Form;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcUser\Form\Register;
use ZfcUser\Form\RegisterFilter;
use ZfcUser\Options;
use ZfcUser\Validator\NoRecordExists;

class RegisterFormFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceManager)
    {
        /* @var $options Options\ModuleOptions */
        $options = $serviceManager->get('zfcuser_module_options');

        $userMapper = $serviceManager->get('zfcuser_user_mapper');

        $emailValidator = new NoRecordExists([
            'mapper' => $userMapper,
            'key'    => 'email',
        ]);

        $userNameValidator = new NoRecordExists([
            'mapper' => $userMapper,
            'key'    => 'username',
        ]);

        $inputFilter = new RegisterFilter(
            $emailValidator,
            $userNameValidator,
            $options
        );

        $form = new Register(null, $options);
        // $form->setCaptchaElement($sm->get('zfcuser_captcha_element'));
        $form->setInputFilter($inputFilter);

        return $form;
    }
}
