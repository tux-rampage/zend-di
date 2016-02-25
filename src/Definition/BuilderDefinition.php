<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition;

use Zend\Di\Exception;

/**
 * Definition builder
 *
 * The definition builder allows creating class definitions programmatically
 *
 * **Example:**
 * ```php
 * $builder = new BuilderDefinition();
 * $builder->createClass(Foo::class)
 *     ->createMethod('setBar')
 *     ->addParameter('bar', Bar::class, true);
 * ```
 *
 * @todo Rename to DefintionBuilder
 * @uses Builder\PhpClass           The class builder
 * @uses Builder\InjectionMethod    The method builder
 */
class BuilderDefinition implements DefinitionInterface
{
    /**
     * @var string
     */
    protected $defaultClassBuilder = 'Zend\Di\Definition\Builder\PhpClass';

    /**
     * @var Builder\PhpClass[]
     */
    protected $classes = [];

    /**
     * Add class
     *
     * @param  Builder\PhpClass  $phpClass
     * @return BuilderDefinition
     */
    public function addClass(Builder\PhpClass $phpClass)
    {
        $this->classes[] = $phpClass;

        return $this;
    }

    /**
     * Create a class builder object using default class builder class
     *
     * This method is a factory that can be used in place of addClass().
     *
     * @param  null|string      $name Optional name of class to assign
     * @return Builder\PhpClass
     */
    public function createClass($name = null)
    {
        $builderClass = $this->defaultClassBuilder;
        /* @var $class Builder\PhpClass */
        $class = new $builderClass();

        if (null !== $name) {
            $class->setName($name);
        }

        $this->addClass($class);

        return $class;
    }

    /**
     * Set the class to use with {@link createClass()}
     *
     * @param  string            $class
     * @return BuilderDefinition
     */
    public function setClassBuilder($class)
    {
        $this->defaultClassBuilder = $class;

        return $this;
    }

    /**
     * Get the class used for {@link createClass()}
     *
     * This is primarily to allow developers to temporarily override
     * the builder strategy.
     *
     * @return string
     */
    public function getClassBuilder()
    {
        return $this->defaultClassBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getClasses()
    {
        $classNames = [];

        /* @var $class Builder\PhpClass */
        foreach ($this->classes as $class) {
            $classNames[] = $class->getName();
        }

        return $classNames;
    }

    /**
     * {@inheritDoc}
     */
    public function hasClass($class)
    {
        foreach ($this->classes as $classObj) {
            if ($classObj->getName() === $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string                $name
     * @return bool|Builder\PhpClass
     */
    protected function getClass($name)
    {
        foreach ($this->classes as $classObj) {
            if ($classObj->getName() === $name) {
                return $classObj;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     * @throws \Zend\Di\Exception\RuntimeException
     */
    public function getClassSupertypes($class)
    {
        $class = $this->getClass($class);

        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }

        return $class->getSuperTypes();
    }

    /**
     * {@inheritDoc}
     * @throws \Zend\Di\Exception\RuntimeException
     */
    public function getInstantiator($class)
    {
        $class = $this->getClass($class);
        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }

        return $class->getInstantiator();
    }

    /**
     * {@inheritDoc}
     * @throws \Zend\Di\Exception\RuntimeException
     */
    public function hasMethods($class)
    {
        /* @var $class \Zend\Di\Definition\Builder\PhpClass */
        $class = $this->getClass($class);
        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }

        return (count($class->getInjectionMethods()) > 0);
    }

    /**
     * {@inheritDoc}
     * @throws \Zend\Di\Exception\RuntimeException
     */
    public function getMethods($class)
    {
        $class = $this->getClass($class);
        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }

        $methods = $class->getInjectionMethods();
        $methodNames = [];

        /* @var $methodObj Builder\InjectionMethod */
        foreach ($methods as $methodObj) {
            $methodNames[] = $methodObj->getName();
        }

        return $methodNames;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\DefinitionInterface::getMethodRequirementType()
     */
    public function getMethodRequirementType($class, $method)
    {
        $class = $this->getClass($class);
        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }

        $method = $class->getInjectionMethod($method);

        if (!$method) {
            return self::METHOD_IS_OPTIONAL;
        }

        return $method->getRequirementType();
    }

    /**
     * {@inheritDoc}
     * @throws \Zend\Di\Exception\RuntimeException
     */
    public function hasMethod($class, $method)
    {
        $class = $this->getClass($class);
        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }
        $methods = $class->getInjectionMethods();

        /* @var $methodObj Builder\InjectionMethod */
        foreach ($methods as $methodObj) {
            if ($methodObj->getName() === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethodParameters($class, $method)
    {
        $class = $this->getClass($class);
        if ($class === false) {
            return false;
        }
        $methods = $class->getInjectionMethods();
        /* @var $methodObj Builder\InjectionMethod */
        foreach ($methods as $methodObj) {
            if ($methodObj->getName() === $method) {
                $method = $methodObj;
            }
        }
        if (!$method instanceof Builder\InjectionMethod) {
            return false;
        }

        /* @var $method Builder\InjectionMethod */

        return (count($method->getParameters()) > 0);
    }

    /**
     * {@inheritDoc}
     * @throws \Zend\Di\Exception\RuntimeException
     */
    public function getMethodParameters($class, $method)
    {
        $class = $this->getClass($class);

        if ($class === false) {
            throw new Exception\RuntimeException('Cannot find class object in this builder definition.');
        }

        $methods = $class->getInjectionMethods();

        /* @var $methodObj Builder\InjectionMethod */
        foreach ($methods as $methodObj) {
            if ($methodObj->getName() === $method) {
                $method = $methodObj;
            }
        }

        if (!$method instanceof Builder\InjectionMethod) {
            throw new Exception\RuntimeException('Cannot find method object for method ' . $method . ' in this builder definition.');
        }

        $methodParameters = [];

        /* @var $method Builder\InjectionMethod */
        foreach ($method->getParameters() as $name => $info) {
            $methodParameters[$class->getName() . '::' . $method->getName() . ':' . $name] = $info;
        }

        return $methodParameters;
    }
}
