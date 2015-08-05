<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use Closure;

/**
 * Default service locator implementation using the dependency injector to
 * create instances
 */
class ServiceLocator implements LocatorInterface
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
    }

    /**
     * Explicitly set a service
     *
     * @param string $name     The name of the service retrievable by get()
     * @param object $service  The service instance
     * @return self
     */
    public function set($name, $service)
    {
        $this->services[$name] = $service;
        return $this;
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
     * @param  string $name
     * @param  array  $params
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $service = $this->di->newInstance($name, false);

        $this->set($name, $service);
        $this->di->injectDependencies($service, $name);

        return $service;
    }
}
