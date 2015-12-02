<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Resolver;

use Zend\Di\ServiceLocatorInterface;

/**
 * Interface for implementing dependency resolvers
 *
 * The dependency resolver is used by the dependency injector or the
 * code generator to gather the types and values to inject
 */
interface DependencyResolverInterface
{
    /**
     * Set the service locator to utilize
     *
     * @param  ServiceLocatorInterface $serviceLocator  Service locator instance
     * @return self Should provide a fluent interface
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator);

    /**
     * Resolve a type prefernece
     *
     * @param string  $type           The type/class name to resolve the preference for
     * @param string  $requestedType  The typename requested by DependencyInjectionInterface::newInstance()
     * @return string Returns the preferred type name
     */
    public function resolvePreference($type, $requestedType = null);

    /**
     * Resolves all parameters for a method
     *
     * @param string $requestedType The requested type
     * @param string $method        The method name
     * @return array|null           Returns the injection parameters as positional array
     *                              This array contains either the class/alias names as string
     *                              or ValueInjection instances
     */
    public function resolveMethodParameters($requestedType, $method, $require = false);
}
