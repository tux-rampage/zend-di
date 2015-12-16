<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Resolver;

use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\ConfigInterface;
use Zend\Di\ServiceLocatorInterface;

/**
 * The default resolver implementation
 */
class DependencyResolver implements DependencyResolverInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    /**
     * @var string[]
     */
    protected $internalTypes = [
        'string', 'int', 'bool', 'float', 'double', 'array', 'resource', 'callable'
    ];

    /**
     * @param DefinitionInterface $definition
     * @param ConfigInterface $instanceConfig
     */
    public function __construct(DefinitionInterface $definition, ConfigInterface $config)
    {
        $this->definition = $definition;
        $this->config = $config;
    }

    /**
     * Returns the configured injections for the requested type
     *
     * If type is an alias it will try to fall back to the class configuration
     *
     * @param  string $requestedType  The type name to get injections for
     * @param  string $method         The method name
     * @return array                  Injections for the method indexed by the parameter name
     */
    protected function getConfiguredInjections($requestedType, $method)
    {
        // Are there any injections defined?
        $injections = $this->config->getInjections($requestedType, $method);

        if (empty($injections) && $this->config->isAlias($requestedType)) {
            $class = $this->config->getClassForAlias($requestedType);

            return $this->config->getInjections($class, $method);
        }

        return $injections;
    }

    /**
     * Check if $type sadisfies $requiredType
     *
     * @param  string $type          The type to check
     * @param  string $requiredType  The required type to check against
     * @return bool
     */
    protected function isTypeOf($type, $requiredType)
    {
        if ($this->config->isAlias($type)) {
            $type = $this->config->getClassForAlias($type);
        }

        if ($type == $requiredType) {
            return true;
        }

        if (!$this->definition->hasClass($type)) {
            return false;
        }

        $superTypes = $this->definition->getClassSupertypes($type);
        return in_array($requiredType, $superTypes);
    }

    /**
     * Check if the given value sadisfies the given type
     *
     * @param  mixed  $value  The value to check
     * @param  string $type   The typename to check against
     * @return bool
     */
    protected function isInstanceOf($value, $type)
    {
        if (!$this->isInternalType($type)) {
            return ($value instanceof $type);
        }

        if ($type == 'callable') {
            return is_callable($value);
        }

        return ($type == gettype($value));
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function isInternalType($type)
    {
        return in_array($type, $this->internalTypes);
    }

    /**
     * @see \Zend\Di\Resolver\DependencyResolverInterface::setServiceLocator()
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * @see \Zend\Di\Resolver\DependencyResolverInterface::resolveMethodParameters()
     */
    public function resolveMethodParameters($requestedType, $method)
    {
        $injections = $this->getConfiguredInjections($requestedType, $method);
        $class = $requestedType;

        if ($this->config->isAlias($requestedType)) {
            $class = $this->config->getClassForAlias($requestedType);
        }

        if (empty($injections)) { // Make sure null won't fail
            $injections = [];
        }

        $result = [];
        $params = $this->definition->getMethodParameters($class, $method);

        foreach ($params as $pramInfo) {
            list($name, $type, $isRequired, $default) = $pramInfo;

            // There is a configured injection and it should not use the type preferences
            if (isset($injections[$name]) && ($injections[$name] != '*')) {
                $injection = $injections[$name];

                // Case: The definition does not require a type - any value is allowed
                if (!$type) {
                    $result[] = new ValueInjection($injection);
                    continue;
                }

                // Case: Definition requires a class or interface
                // -> Use configured injection only if it is a string that contains a typename which sadisfies the
                //    required type
                if (is_string($injection) && !$this->isInternalType($type) && $this->isTypeOf($injection, $type)) {
                    $result[] = $injection;
                    continue;
                }

                // Finally check if the injection value can be used to fulfill the requirement
                if ($this->isInstanceOf($injection, $type)) {
                    $result[] = new ValueInjection($injection);
                    continue;
                }
            }

            // No match in configured injections try to find the type preference
            if ($type && !$this->isInternalType($type)) {
                $preference = $this->resolvePreference($type, $requestedType);

                if ($preference) {
                    $result[] = $preference;
                    continue;
                }
            }

            // The parameter is required, but we can't find anything that ist suitable
            if ($isRequired) {
                return null;
            }

            // Use the defintion provided default
            $result[] = new ValueInjection($default);
        }

        return $result;
    }

    /**
     * @see \Zend\Di\Resolver\DependencyResolverInterface::resolvePreference()
     */
    public function resolvePreference($type, $requestedType = null)
    {
        // TODO Auto-generated method stub

    }
}