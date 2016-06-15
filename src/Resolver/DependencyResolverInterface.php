<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Resolver;

use Interop\Container\ContainerInterface;

/**
 * Interface for implementing dependency resolvers
 *
 * The dependency resolver is used by the dependency injector or the
 * code generator to gather the types and values to inject
 */
interface DependencyResolverInterface
{
    /**
     * Resolver Policy: Require as many things as possible
     *
     * This will cause the resolver to auto-wire all eager
     * dependencies known by the definition.
     */
    const RESOLVE_EAGER = 5;

    /**
     * Resolver Policy: Fail on  required missing dependencies
     *
     * This will cause the resolver to fail on non-essential
     * (eager) dependencies as well.
     */
    const RESOLVE_STRICT = 3;

    /**
     * Resolver Policy: Only essentially required injections
     *
     * This will cause the resolver to only fail on methods marked as
     * Required.
     */
    const RESOLVE_ESSENTIAL = 1;

    /**
     * Set the ioc container
     *
     * @param   ContainerInterface  $container  The ioc container to utilize for checking for instances
     * @return  self                            Should provide a fluent interface
     */
    public function setContainer(ContainerInterface $container);

    /**
     * Resolve a type prefernece
     *
     * @param   string  $dependencyType The type/class name of the dependency to resolve the preference for
     * @param   string  $requestedType  The typename of the instance that is created or in which the dependency should be injected
     * @return  string                  Returns the preferred type name or null if there is no preference
     */
    public function resolvePreference($dependencyType, $requestedType = null);

    /**
     * Resolves all parameter types for a method
     *
     * @param  string       $requestedType  The requested type
     * @param  string       $method         The method name
     * @param  array        $parameters     Directly provided parameters for instanciation. Should only apply to instanciators
     * @return array|null                   Returns the injection parameters as positional array
     *                                      This array contains either the class/alias names as string
     *                                      or ValueInjection instances
     */
    public function resolveMethodParameters($requestedType, $method, array $parameters = []);
}
