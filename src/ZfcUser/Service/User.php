<?php

namespace ZfcUser\Service;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Form\Form;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Hydrator;
use ZfcUser\EventManager\EventProvider;
use ZfcUser\Mapper\UserInterface as UserMapperInterface;
use ZfcUser\Options\UserServiceOptionsInterface;

class User extends EventProvider
{
    /**
     * @var UserMapper
     */
    protected $userMapper;

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * @var Form
     */
    protected $loginForm;

    /**
     * @var Form
     */
    protected $registerForm;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var ServiceOptions
     */
    protected $options;

    /**
     * @var Hydrator
     */
    protected $formHydrator;

    /**
     * createFromForm
     *
     * @param array $data
     * @return \ZfcUser\Entity\UserInterface
     * @throws Exception\InvalidArgumentException
     */
    public function register(array $data)
    {
        $entityClass = $this->getOptions()->getUserEntityClass();
        $form        = $this->getRegisterForm();

        $form->setHydrator($this->getFormHydrator());
        $form->bind(new $entityClass);
        $form->setData($data);
        if (!$form->isValid()) {
            return false;
        }

        $user = $form->getData();
        /* @var $user \ZfcUser\Entity\UserInterface */

        $bcrypt = new Bcrypt;
        $bcrypt->setCost($this->getOptions()->getPasswordCost());
        $user->setPassword($bcrypt->create($user->getPassword()));

        if ($this->getOptions()->getEnableUsername()) {
            $user->setUsername($data['username']);
        }
        if ($this->getOptions()->getEnableDisplayName()) {
            $user->setDisplayName($data['display_name']);
        }

        // If user state is enabled, set the default state value
        if ($this->getOptions()->getEnableUserState()) {
            $user->setState($this->getOptions()->getDefaultUserState());
        }
        $this->getEventManager()->trigger(__FUNCTION__, $this, array('user' => $user, 'form' => $form));
        $this->getUserMapper()->insert($user);
        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'form' => $form));
        return $user;
    }

    /**
     * change the current users password
     *
     * @param array $data
     * @return boolean
     */
    public function changePassword(array $data)
    {
        $currentUser = $this->getAuthService()->getIdentity();

        $oldPass = $data['credential'];
        $newPass = $data['newCredential'];

        if ($form->isValid()) {
            $user   = $form->getData();
            $events = $this->getEventManager();

        if (!$bcrypt->verify($oldPass, $currentUser->getPassword())) {
            return false;
        }

        $pass = $bcrypt->create($newPass);
        $currentUser->setPassword($pass);

        $this->getEventManager()->trigger(__FUNCTION__, $this, array('user' => $currentUser, 'data' => $data));
        $this->getUserMapper()->update($currentUser);
        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $currentUser, 'data' => $data));

        return true;
    }

    public function changeEmail(array $data)
    {
        $currentUser = $this->getAuthService()->getIdentity();

            $events->trigger(__FUNCTION__, $this, compact('user', 'form'));
            $this->getUserMapper()->insert($user);
            $events->trigger(__FUNCTION__.'.post', $this, compact('user', 'form'));

            return $user;
        }

        $currentUser->setEmail($data['newIdentity']);

        $this->getEventManager()->trigger(__FUNCTION__, $this, array('user' => $currentUser, 'data' => $data));
        $this->getUserMapper()->update($currentUser);
        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $currentUser, 'data' => $data));

        return true;
    }

    /**
     * getUserMapper
     *
     * @return UserMapper
     */
    public function getUserMapper()
    {
        if (null === $this->userMapper) {
            $this->setUserMapper($this->serviceManager->get('zfcuser_user_mapper'));
        }
        return $this->userMapper;
    }

    /**
     * setUserMapper
     *
     * @param UserMapperInterface $userMapper
     * @return User
     */
    public function setUserMapper(UserMapper $userMapper)
    {
        $this->userMapper = $userMapper;
        return $this;
    }

    /**
     * getAuthService
     *
     * @return AuthenticationService
     */
    public function getAuthService()
    {
        if (null === $this->authService) {
            $this->setAuthService($this->serviceManager->get('zfcuser_auth_service'));
        }
        return $this->authService;
    }

    /**
     * setAuthenticationService
     *
     * @param AuthenticationService $authService
     * @return User
     */
    public function setAuthService(AuthenticationService $authService)
    {
        $this->authService = $authService;
        return $this;
    }

    /**
     * @return Form
     */
    public function getRegisterForm()
    {
        if (null === $this->registerForm) {
            $this->setRegisterForm($this->serviceManager->get('zfcuser_register_form'));
        }
        return $this->registerForm;
    }

    /**
     * @param Form $registerForm
     * @return User
     */
    public function setRegisterForm(Form $registerForm)
    {
        $this->registerForm = $registerForm;
        return $this;
    }

    /**
     * get service options
     *
     * @return UserServiceOptionsInterface
     */
    public function getOptions()
    {
        if (!$this->options instanceof ServiceOptions) {
            $this->setOptions($this->serviceManager->get('zfcuser_module_options'));
        }
        return $this->options;
    }

    /**
     * set service options
     *
     * @param ServiceOptions $options
     */
    public function setOptions(ServiceOptions $options)
    {
        $this->options = $options;
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ContainerInterface $serviceManager
     * @return User
     */
    public function setServiceManager(ContainerInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    /**
     * Return the Form Hydrator
     *
     * @return \Laminas\Hydrator\ClassMethods
     */
    public function getFormHydrator()
    {
        if (!$this->formHydrator instanceof Hydrator) {
            $this->setFormHydrator(
                $this->serviceManager->get('zfcuser_user_hydrator')
            );
        }

        return $this->formHydrator;
    }

    /**
     * Set the Form Hydrator to use
     *
     * @param Hydrator $formHydrator
     * @return User
     */
    public function setFormHydrator(Hydrator $formHydrator)
    {
        $this->formHydrator = $formHydrator;
        return $this;
    }
}
