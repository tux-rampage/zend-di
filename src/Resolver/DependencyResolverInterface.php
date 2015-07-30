<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Resolver;


/**
 * Interface for implementing dependency resolvers
 *
 * The dependency resolver is used by the dependency injector or the
 * code generator to gather the types and values to inject
 */
class DependencyResolverInterface
{
    /**
     * Flag to mark the result as type name.
     *
     * The injector should use the instance manager to obtain the
     * object to inject.
     */
    const RESULT_TYPENAME = 0;

    /**
     * Marks the result as value
     *
     * The injector should use the returned value and inject
     * it directly.
     *
     * The value must be scalar.
     */
    const RESULT_VAUE = 1;

    /**
     * Resolve a type prefernece
     *
     * @param string  $type           The type/class name to resolve the preference for
     * @param string  $requestedType  The typename requested by DependencyInjectionInterface::newInstance()
     * @return string Returns the preferred type name
     */
    public function resolvePreference($type, $requestedType = null);

    /**
     * Resolves the type to inject into a property
     *
     * @param  string  $requestedType  The typename requested by DependencyInjectionInterface::newInstance()
     * @param  string  $property       The property name to inject
     * @return array   The resulting type and injection value: array(result, typeOrValue)
     */
    public function resolveProperty($requestedType, $property);

    /**
     * Resolves all parameters for a method
     *
     * @param string $requestedType
     * @param string $method
     */
    public function resolveMethodParameters($requestedType, $method);
}
