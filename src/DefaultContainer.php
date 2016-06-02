<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use Interop\Container\ContainerInterface;


/**
 * Default ioc container implementation
 *
 * This is using the dependency injector to create instances
 */
class DefaultContainer implements ContainerInterface
{
    /**
     * Dependency injector
     *
     * @var DependencyInjectionInterface
     */
    protected $di;

    /**
     * Registered services and cached values
     *
     * @var array
     */
    protected $services = [];

    /**
     * @param DependencyInjectionInterface $di
     */
    public function __construct(DependencyInjectionInterface $di)
    {
        $this->di = $di;

        $this->services[DependencyInjectionInterface::class] = $di;
        $this->services[ContainerInterface::class] = $this;
    }

    /**
     * Explicitly set a service
     *
     * @param string $name     The name of the service retrievable by get()
     * @param object $service  The service instance
     * @return self
     */
    public function setInstance($name, $service)
    {
        $this->services[$name] = $service;
        return $this;
    }

    /**
     * @see \Interop\Container\ContainerInterface::has()
     */
    public function has($name)
    {
        if (isset($this->services[$name])) {
            return true;
        }

        return $this->di->canInstanciate($name);
    }

    /**
     * Retrieve a service
     *
     * Tests first if a service is registered, and, if so,
     * returns it.
     *
     * If the service is not yet registered, it is attempted to be created via
     * the dependency injector and then it is stored for further use.
     *
     * @see \Interop\Container\ContainerInterface::get()
     * @param  string $name
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $service = $this->di->newInstance($name);

        $this->setInstance($name, $service);
        return $service;
    }
}
