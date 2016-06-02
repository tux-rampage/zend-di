<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;


interface ConfigInterface
{
    /**
     * Check if the provided type name is aliased
     *
     * @param  string $name
     * @return bool
     */
    public function isAlias($name);

    /**
     * Returns the actual class name for an alias
     *
     * @param  string $name
     * @return string
     */
    public function getClassForAlias($name);

    /**
     * Returns the instanciation paramters for the given type
     *
     * @param   string  $type   The alias or class name
     * @return  array           The configured parameters
     */
    public function getParameters($type);

    /**
     * Returns the injections for a specific method
     *
     * The renurned array contains the parameter name as key
     * and the injection as value. If this value is "*", the type preferences should be used.
     * String values will be used as type name if the method parameter is typehintet to a class or an interface
     *
     * TODO: Should this also support positional paramter configs?
     *
     * @param  string $type
     * @param  string $method
     * @return array
     */
    public function getInjections($type, $method);

    /**
     * Returns all methods that have configured injections
     *
     * @param  string $type
     * @return string[]
     */
    public function getAllInjectionMethods($type);

    /**
     * Get global type preferences
     *
     * @param  string $type
     * @return string[]
     */
    public function getTypePreferences($type);

    /**
     * Get a type preferences for a specific class or alias
     *
     * @param  string $type
     * @param  string $classOrAlias
     * @return string[]
     */
    public function getTypePreferencesForClass($type, $classOrAlias);
}
