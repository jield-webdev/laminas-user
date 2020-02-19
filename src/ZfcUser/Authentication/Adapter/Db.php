<?php

namespace ZfcUser\Authentication\Adapter;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Container as SessionContainer;
use ZfcUser\Entity\UserInterface;
use ZfcUser\Options\ModuleOptions;

class Db extends AbstractAdapter
{
    /**
     * @var UserMapper
     */
    protected $mapper;

    /**
     * @var callable
     */
    protected $credentialPreprocessor;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var ModuleOptions
     */
    protected $options;

    /**
     * Called when user id logged out
     * @param AdapterChainEvent $e
     */
    public function logout(AdapterChainEvent $e)
    {
        $this->getStorage()->clear();
    }

    /**
     * @param AdapterChainEvent $e
     * @return bool
     */
    public function authenticate(AdapterChainEvent $e)
    {
        if ($this->isSatisfied()) {
            $storage = $this->getStorage()->read();
            $event->setIdentity($storage['identity'])
                ->setCode(AuthenticationResult::SUCCESS)
                ->setMessages(['Authentication successful.']);
            return;
        }

        $identity   = $event->getRequest()->getPost()->get('identity');
        $credential = $event->getRequest()->getPost()->get('credential');
        $credential = $this->preProcessCredential($credential);
        /** @var UserInterface|null $userObject */
        $userObject = null;

        // Cycle through the configured identity sources and test each
        $fields = $this->getOptions()->getAuthIdentityFields();
        while (!is_object($userObject) && count($fields) > 0) {
            $mode = array_shift($fields);
            switch ($mode) {
                case 'username':
                    $userObject = $this->getMapper()->findByUsername($identity);
                    break;
                case 'email':
                    $userObject = $this->getMapper()->findByEmail($identity);
                    break;
            }
        }

        if (!$userObject) {
            $event->setCode(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND)
                ->setMessages(['A record with the supplied identity could not be found.']);
            $this->setSatisfied(false);
            return false;
        }

        if ($this->getOptions()->getEnableUserState()) {
            // Don't allow user to login if state is not in allowed list
            if (!in_array($userObject->getState(), $this->getOptions()->getAllowedLoginStates())) {
                $event->setCode(AuthenticationResult::FAILURE_UNCATEGORIZED)
                    ->setMessages(['A record with the supplied identity is not active.']);
                $this->setSatisfied(false);
                return false;
            }
        }

        $cryptoService = $this->getHydrator()->getCryptoService();
        if (!$cryptoService->verify($credential, $userObject->getPassword())) {
            // Password does not match
            $event->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
                ->setMessages(['Supplied credential is invalid.']);
            $this->setSatisfied(false);
            return false;
        } elseif ($cryptoService instanceof Bcrypt) {
            // Update user's password hash if the cost parameter has changed
            $this->updateUserPasswordHash($userObject, $credential, $cryptoService);
        }

        // regen the id
        SessionContainer::getDefaultManager()->regenerateId();

        // Success!
        $event->setIdentity($userObject->getId());

        $this->setSatisfied(true);
        $storage             = $this->getStorage()->read();
        $storage['identity'] = $event->getIdentity();
        $this->getStorage()->write($storage);
        $event->setCode(AuthenticationResult::SUCCESS)
            ->setMessages(['Authentication successful.']);
    }

    public function preProcessCredential($credential)
    {
        if (is_callable($this->credentialPreprocessor)) {
            return call_user_func($this->credentialPreprocessor, $credential);
        }

        return $credential;
    }

    /**
     * @return ModuleOptions
     */
    public function getOptions()
    {
        if ($this->options === null) {
            $this->setOptions($this->getServiceManager()->get('zfcuser_module_options'));
        }

        return $this->options;
    }

    /**
     * @param ModuleOptions $options
     */
    public function setOptions(ModuleOptions $options)
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
     */
    public function setServiceManager(ContainerInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * getMapper
     *
     * @return UserMapper
     */
    public function getMapper()
    {
        if (!$this->mapper instanceof UserMapper) {
            $this->setMapper($this->serviceManager->get('zfcuser_user_mapper'));
        }

        return $this->mapper;
    }

    /**
     * setMapper
     *
     * @param UserMapper $mapper
     * @return Db
     */
    public function setMapper(UserMapper $mapper)
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * Lazy-loads a hydrator from the service manager
     *
     * @return Hydrator
     */
    public function getHydrator()
    {
        if (!$this->hydrator instanceof Hydrator) {
            $this->setHydrator($this->serviceManager->get('zfcuser_user_hydrator'));
        }
        return $this->hydrator;
    }

    /**
     * Set the hydrator
     *
     * @param Hydrator $hydrator
     */
    public function setHydrator(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    protected function updateUserPasswordHash(UserInterface $userObject, $password, Bcrypt $bcrypt)
    {
        $hash = explode('$', $user->getPassword());
        if ($hash[2] === $bcrypt->getCost()) {
            return;
        }
        $user = $this->getHydrator()->hydrate(compact('password'), $user);
        $this->getMapper()->update($user);
    }

    /**
     * Get credentialPreprocessor.
     *
     * @return callable
     */
    public function getCredentialPreprocessor()
    {
        return $this->credentialPreprocessor;
    }

    /**
     * Set credentialPreprocessor.
     *
     * @param callable $credentialPreprocessor
     * @return $this
     */
    public function setCredentialPreprocessor($credentialPreprocessor)
    {
        if (!is_callable($credentialPreprocessor)) {
            $message = sprintf(
                "Credential Preprocessor must be callable, [%s] given",
                gettype($credentialPreprocessor)
            );
            throw new InvalidArgumentException($message);
        }
        $this->credentialPreprocessor = $credentialPreprocessor;
    }
}
