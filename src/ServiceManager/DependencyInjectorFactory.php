<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\ServiceManager;

use Interop\Container\ContainerInterface;
use Zend\Di\Config;
use Zend\Di\DependencyInjector;
use Zend\Di\Generated\DependencyInjector as GeneratedDependencyInjector;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Di\Definition\DefinitionInterface;


/**
 * Implements the DependencyInjector service factory for zend-servicemanager
 */
class DependencyInjectorFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $configData = ($container->has('config'))? $container->get('config') : [];
        $definitions = $container->has(DefinitionInterface::class)? $container->get(DefinitionInterface::class) : null;

        $config = new Config(isset($configData['di'])? $configData['di'] : []);

        if (class_exists(GeneratedDependencyInjector::class)) {
            $injector = new GeneratedDependencyInjector($config, $definitions, null, $container);
        } else {
            $injector = new DependencyInjector($config, $definitions, null, $container);
        }

        return $injector;
    }
}
