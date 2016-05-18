<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\ServiceManager;

use Zend\Di\DependencyInjectionInterface;
use Zend\Di\Exception\RuntimeException;

use Interop\Container\ContainerInterface;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;


/**
 * Implements the abstract di service factory
 *
 * This factory creates instances vi the dependency injector by class name
 */
class DependencyInjectedAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Retrieves the injector from a container
     *
     * @param   ContainerInterface              $container  The container context for this factory
     * @return  DependencyInjectionInterface                The dependency injector
     * @throws  \Zend\Di\Exception\RuntimeException         Thrown when no dependency injector is available
     */
    private function getInjector(ContainerInterface $container)
    {
        $injector = $container->get(DependencyInjectionInterface::class);

        if (!$injector instanceof DependencyInjectionInterface) {
            throw new RuntimeException('Could not get a dependency injector form the container implementation');
        }

        return $injector;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\AbstractFactoryInterface::canCreate()
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (!$container->has(DependencyInjectionInterface::class)) {
            return false;
        }

        return $this->getInjector($container)->canInstanciate($requestedName);
    }

    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return $this->getInjector($container)->newInstance($requestedName, $options);
    }
}
