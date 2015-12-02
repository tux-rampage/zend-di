<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition;

interface DefinitionInterface
{
    /**
     * Retrieves all classes in this definition
     *
     * @return string[]
     */
    public function getClasses();

    /**
     * Return whether the class exists in this definition
     *
     * @param  string $class
     * @return bool
     */
    public function hasClass($class);

    /**
     * Return the supertypes for this class
     *
     * @param  string   $class
     * @return string[]
     */
    public function getClassSupertypes($class);

    /**
     * @param  string       $class
     * @return string|array
     */
    public function getInstantiator($class);

    /**
     * Return if there are injection methods
     *
     * @param  string $class
     * @return bool
     */
    public function hasMethods($class);

    /**
     * Return an array of the injection methods for a given class
     *
     * @param  string   $class
     * @return string[]
     */
    public function getMethods($class);

    /**
     * Check whether the the method exists for the given class
     *
     * @param  string $class
     * @param  string $method
     * @return bool
     */
    public function hasMethod($class, $method);

    /**
     * Check if the method has parameters
     *
     * @param $class
     * @param $method
     * @return bool
     */
    public function hasMethodParameters($class, $method);

    /**
     * getMethodParameters() return information about a methods parameters.
     *
     * Should return an ordered named array of parameters for a given method. The keys
     * should represent the full qualified parameter position.
     * Each value should be an array, of length 4 with the following information:
     *
     * <ol>
     *   <li>[string] The parameter name</li>
     *   <li>[string|null] The required type. If this is null any type might be acceptable</li>
     *   <li>[bool] Flag if the parameter is required</li>
     *   <li>[mixed] The default value for this parameter</li>
     * </ol>
     *
     * @param  string $class
     * @param  string $method
     * @return array
     */
    public function getMethodParameters($class, $method);
}
