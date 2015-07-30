<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

/**
 * The default instance manager implementation
 *
 * This only keeps already instanciated classes
 */
class InstanceManager implements DependencyInjectionAwareInterface, InstanceManagerInterface
{
    /**
     * @var DependencyInjectionInterface
     */
    protected $di;

    /**
     * Array of shared instances
     *
     * @var object[]
     */
    protected $sharedInstances = [];


    /**
     * @see \Zend\Di\InstanceManagerInterface::get()
     */
    public function get($name)
    {
        if (isset($this->sharedInstances[$name])) {
            return $this->sharedInstances[$name];
        }

        $instance = $this->di->newInstance($name, false);
        $this->sharedInstances[$name] = $instance;

        $this->di->injectDependencies($instance, $name);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return (isset($this->sharedInstances[$name]) || $this->di->canInstanciate($name));
    }

    /**
     * {@inheritdoc}
     * @return self Fluent interface
     */
    public function setDependencyInjector(DependencyInjectionInterface $di)
    {
        $this->di = $di;
        return $this;
    }
}
