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
 * A stand alone ioc container implementation
 *
 * This is intentionally compatible to the ServiceManager's ServiceLocatorInterface. It's just not implementing it
 * to not introduce a non-dev dependency to the ServiceManager component
 */
class Container extends DefaultContainer
{
    /**
     * {@inheritDoc}
     * @see \Zend\Di\ServiceLocator::__construct()
     */
    public function __construct(DependencyInjectionInterface $di = null)
    {
        if (!$di) {
            $di = new DependencyInjector(null, null, null, $this);
        } else {
            $di->setServiceLocator($this);
        }

        parent::__construct($di);
    }

    /**
     * Create a completely new instance for the requested class or alias name
     *
     * @param  string $name      The Class or alias name to create
     * @param  array  $options   The constructor options for building
     * @throws Exception\ExceptionInterface
     * @return object
     */
    public function build($name, array $options = [])
    {
        return $this->di->newInstance($name, $options);
    }
}
