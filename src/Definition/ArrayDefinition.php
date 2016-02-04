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
 * Class definitions based on a given array
 */
class ArrayDefinition implements DefinitionInterface
{
    /**
     * @var array
     */
    protected $definition = [];

    /**
     * @param array $dataArray
     */
    public function __construct(array $dataArray)
    {
        $this->definition = $dataArray;
    }

    /**
     * {@inheritDoc}
     */
    public function getClasses()
    {
        return array_keys($this->definition);
    }

    /**
     * {@inheritDoc}
     */
    public function hasClass($class)
    {
        return array_key_exists($class, $this->definition);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassSupertypes($class)
    {
        if (!isset($this->definition[$class])) {
            return [];
        }

        if (!isset($this->definition[$class]['supertypes'])) {
            return [];
        }

        return $this->definition[$class]['supertypes'];
    }

    /**
     * {@inheritDoc}
     */
    public function getInstantiator($class)
    {
        if (!isset($this->definition[$class])) {
            return;
        }

        if (!isset($this->definition[$class]['instantiator'])) {
            return '__construct';
        }

        return $this->definition[$class]['instantiator'];
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethods($class)
    {
        if (!isset($this->definition[$class])) {
            return false;
        }

        if (!isset($this->definition[$class]['methods'])) {
            return false;
        }

        return (count($this->definition[$class]['methods']) > 0);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($class, $method)
    {
        if (!isset($this->definition[$class])) {
            return false;
        }

        if (!isset($this->definition[$class]['methods'])) {
            return false;
        }

        return array_key_exists($method, $this->definition[$class]['methods']);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods($class)
    {
        if (!isset($this->definition[$class])) {
            return [];
        }

        if (!isset($this->definition[$class]['methods'])) {
            return [];
        }

        return $this->definition[$class]['methods'];
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethodParameters($class, $method)
    {
        return isset($this->definition[$class]['parameters'][$method]);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodParameters($class, $method)
    {
        if (!isset($this->definition[$class])) {
            return [];
        }

        if (!isset($this->definition[$class]['parameters'])) {
            return [];
        }

        if (!isset($this->definition[$class]['parameters'][$method])) {
            return [];
        }

        return $this->definition[$class]['parameters'][$method];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->definition;
    }
}
