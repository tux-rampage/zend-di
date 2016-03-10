<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Introspection;

use Zend\Code\Annotation\AnnotationManager;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Interface definition for introspection strategies
 */
interface StrategyInterface
{
    /**
     * Reflect the parameter type
     *
     * This should return the required type name. This might
     * either be a FQCN or a buildin type name (i.e. string or int)
     *
     * @param \ReflectionParameter $parameter
     * @return string|null
     */
    public function reflectParameterType(ReflectionParameter $parameter);

    /**
     * Returns whether to use annotations or not
     *
     * @return bool
     */
    public function getUseAnnotations();

    /**
     * Returns the annotation manager to use for reflecting a method
     *
     * @return AnnotationManager
     */
    public function getAnnotationManager();

    /**
     * Checks the reflected method it is allowed for dependency injection
     *
     * @param   ReflectionMethod $reflectedMethod
     * @return  bool
     */
    public function includeMethod(ReflectionMethod $reflectedMethod);

    /**
     * Checks whether the interface methods should be added tothe definition or not
     *
     * @param   ReflectionClass $reflectedInterface
     * @return  bool
     */
    public function includeInterfaceMethods(ReflectionClass $reflectedInterface);
}
