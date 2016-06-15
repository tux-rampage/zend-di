<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition;

/**
 * Interface for class definitions
 */
interface DefinitionInterface
{
    /**
     * The method is completely optional and may be omitted if no dependencies can be provided even in strict mode
     */
    const METHOD_IS_OPTIONAL = 0;

    /**
     * Marks the method as requirement
     *
     * Missing dependencies will always fail
     */
    const METHOD_IS_REQUIRED = 1; // ConfigInterface::MODE_EAGER | ConfigInterface::MODE_STRICT

    /**
     * Marks a method as requirement in strict resolver mode
     */
    const METHOD_IS_EAGER = 2;

    /**
     * The method is a constructor
     */
    const METHOD_IS_CONSTRUCTOR = self::METHOD_IS_REQUIRED;

    /**
     * Definitions comming from interfaces (Aware)
     */
    const METHOD_IS_AWARE = self::METHOD_IS_EAGER;

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
     * Returns the instanciator for the class
     *
     * The instanciator must be either a static method name of the class, or
     * "__construct"
     *
     * @param  string       $class  The class name to get the instanciator for
     * @return string|null          The instanciator method or null if the defintion cannot provide one
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
     * Returns the methods requirement type
     *
     * @param  string   $class
     * @param  string   $method
     * @return int
     */
    public function getMethodRequirementType($class, $method);

    /**
     * Check whether the the method is defined as injectable for the given class
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
     * should represent the parameter name as defined in code.
     *
     * @param  string $class
     * @param  string $method
     * @return MethodParameter[]
     */
    public function getMethodParameters($class, $method);
}
