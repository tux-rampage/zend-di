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

use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\ConfigInterface;
use Zend\Di\Exception\MissingPropertyException;


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
     * @var ContainerInterface
     */
    protected $serviceLocator = null;

    /**
     * The resolver mode
     *
     * @var int
     */
    protected $mode = self::MODE_STRICT;

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
     * Check if $type satisfies $requiredType
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
     * Set the resolver mode.
     *
     * Changes the resolver mode to strict or eager.
     *
     * See the `MODE_*` constants for details.
     *
     * @param  int  $mode   The new resolver mode
     * @return self
     */
    public function setMode($mode)
    {
        $this->mode = (int)$mode;
        return $this;
    }

    /**
     * @see \Zend\Di\Resolver\DependencyResolverInterface::setServiceLocator()
     */
    public function setServiceLocator(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * Check if the value is a candidate for injection
     *
     * If this is the case return the injectable representation
     *
     * @param mixed $value
     * @param string $requiredType
     * @return null|string|ValueInjection
     */
    protected function checkValueToInject($value, $requiredType)
    {
        // Case configuration enforces a type injection
        if ($value instanceof TypeInjection) {
            if (!$this->isTypeOf($value->getType(), $requiredType)) {
                return null;
            }

            return $value->getType();
        }

        // Case: The definition does not require a type - any value is allowed
        if (!$requiredType) {
            return new ValueInjection($value);
        }

        // Case: Definition requires a class or interface
        // -> Use configured injection only if it is a string that contains a typename which satisfies the
        //    required type
        if (is_string($value) && !$this->isInternalType($requiredType) && $this->isTypeOf($value, $requiredType)) {
            return $value;
        }

        // Finally check if the injection value can be used to fulfill the requirement
        if ($this->isInstanceOf($value, $requiredType)) {
            return new ValueInjection($value);
        }

        // Not applicable
        return null;
    }

    /**
     * {@inheritDoc}
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

        // This method is not known - take the injections as literal
        if (!$this->definition->hasMethod($class, $method)) {
            foreach ($injections as $injection) {
                if ($injection instanceof TypeInjection) {
                    $result[] = $injection->getType();
                } else {
                    $result[] = new ValueInjection($injection);
                }
            }

            return $result;
        }

        $params = $this->definition->getMethodParameters($class, $method);
        $methodRequirement = $this->definition->getMethodRequirementType($class, $method);

        foreach ($params as $paramInfo) {
            $name = $paramInfo->name;
            $type = $paramInfo->type;
            $isRequired = $paramInfo->isRequired;
            $default = $paramInfo->default;

            // There is a directly provided injection - This should only apply to instanciators
            // No attempt is made to resolve anything it's taken as it is
            if (isset($params[$name])) {
                $result[$name] = new ValueInjection($params[$name]);
                continue;
            }

            // There is a configured injection and it should not use the type preferences
            if (isset($injections[$name]) && ($injections[$name] != '*') && (null !== ($injection = $this->checkValueToInject($injections[$name], $type)))) {
                $result[$name] = $injection;
                continue;
            }

            // No match in configured injections try to find the type preference
            if ($type && !$this->isInternalType($type)) {
                $preference = $this->resolvePreference($type, $requestedType);

                if ($preference && ($this->serviceLocator->has($preference))) {
                    $result[$name] = $preference;
                    continue;
                }

                if ($this->serviceLocator->has($type)) {
                    $result[$name] = $type;
                }
            }

            // The parameter is required, but we can't find anything that ist suitable
            if ($isRequired) {
                if (($methodRequirement & $this->mode) != 0) {
                    throw new MissingPropertyException(sprintf('Could not resolve value for ', $class, $method, $name));
                }

                return null;
            }

            // Use the defintion provided default
            $result[$name] = new ValueInjection($default);
        }

        return $result;
    }

    /**
     * @see \Zend\Di\Resolver\DependencyResolverInterface::resolvePreference()
     */
    public function resolvePreference($dependencyType, $requestedType = null)
    {
        $preferences = $this->config->getTypePreferencesForClass($dependencyType, $requestedType);
        $preferences = array_merge($preferences, $this->config->getTypePreferences($dependencyType));

        foreach ($preferences as $preference) {
            if ($this->isTypeOf($preference, $dependencyType) && $this->serviceLocator->has($preference)) {
                return $preference;
            }
        }

        return null;
    }
}
