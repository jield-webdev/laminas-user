<?php

namespace ZfcUser\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Hydrator\ClassMethods;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class UserHydrator implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this->__invoke($serviceLocator, null);
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ClassMethods();
    }
}
