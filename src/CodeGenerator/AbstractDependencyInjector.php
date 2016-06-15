<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;

use Interop\Container\ContainerInterface;
use Zend\Di\ConfigInterface;
use Zend\Di\DependencyInjector;
use Zend\Di\DefinitionList;
use Zend\Di\Resolver\DependencyResolverInterface;
use Zend\Di\Exception\RuntimeException;


/**
 * Abstract class for code generated dependency injectors
 */
abstract class AbstractDependencyInjector extends DependencyInjector
{
    /**
     * @var string|FactoryInterface[]
     */
    private $factories;

    /**
     * {@inheritDoc}
     * @see \Zend\Di\DependencyInjector::__construct()
     */
    public function __construct(ConfigInterface $config = null, DefinitionList $definitions = null, DependencyResolverInterface $resolver = null, ContainerInterface $container = null)
    {
        parent::__construct($config, $definitions, $resolver, $container);
        $this->factories = $this->getFactoryList();
    }

    /**
     * Returns the list of type factories
     *
     * @return  string[string]  The factories keyd by type name
     */
    abstract protected function getFactoryList();

    /**
     * @param string $type
     * @return FactoryInterface
     */
    private function getGeneratedFactory($type)
    {
        if ($this->factories[$type] instanceof FactoryInterface) {
            return $this->factories[$type];
        }

        $file = $this->factories[$type];
        $class = include_once __DIR__ . '/' . $file;

        if (!$class) {
            throw new RuntimeException('Failed to load generated factory for ' . $type);
        }

        $factory = new $class($this->container);
        $this->setFactory($type, $factory);

        return $factory;
    }

    /**
     * Define the factory for a specific type
     *
     * @param   string                          $type       The type name to register to
     * @param   DependencyInjectionInterface    $factory    The factory instance
     */
    protected function setFactory($type, FactoryInterface $factory)
    {
        $this->factories[$type] = $factory;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\DependencyInjector::canInstanciate()
     */
    public function canInstanciate($name)
    {
        if (isset($this->factories[$name])) {
            return true;
        }

        return parent::canInstanciate($name);
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\DependencyInjector::createInstance()
     */
    protected function createInstance($name, $params)
    {
        if (isset($this->factories[$name])) {
            return $this->getGeneratedFactory($name)->create($params);
        }

        return parent::createInstance($name, $params);
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\DependencyInjector::doInjectDependencies()
     */
    protected function doInjectDependencies($instance, $type)
    {
        if (isset($this->factories[$type])) {
            return $this->getGeneratedFactory($type)->injectDependencies($instance);
        }

        parent::doInjectDependencies($instance, $type);
    }
}
