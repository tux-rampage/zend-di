<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition;

use Zend\Code\Annotation\AnnotationCollection;
use Zend\Code\Reflection;

/**
 * Class definitions based on runtime reflection
 */
class RuntimeDefinition extends ArrayDefinition implements DefinitionInterface
{
    /**
     * Flag if classes should be looked up explicitly
     *
     * @var bool
     */
    protected $explicitLookups = false;

    /**
     * @var Introspection\StrategyInterface
     */
    protected $introspectionStrategy = null;

    /**
     * Tracks which classes are processed
     *
     * @var bool[string]
     */
    protected $processedClass = [];

    /**
     * Constructor
     *
     * @param null|IntrospectionStrategy $introspectionStrategy
     * @param array|null                 $explicitClasses
     */
    public function __construct(Introspection\StrategyInterface $introspectionStrategy = null, array $explicitClasses = null)
    {
        $this->introspectionStrategy = ($introspectionStrategy) ?: $this->createDefaultIntrospectionStrategy();

        if ($explicitClasses) {
            $this->setExplicitClasses($explicitClasses);
        }
    }

    /**
     * @return Introspection\StrategyInterface
     */
    protected function createDefaultIntrospectionStrategy()
    {
        if (version_compare(PHP_VERSION, '7', '<')) {
            return new Introspection\Php5Strategy();
        }

        return new Introspection\DefaultStrategy();
    }

    /**
     * Set the introspection strategy for reflecting classes
     *
     * @param  Introspection\StrategyInterface $introspectionStrategy
     * @return self
     */
    public function setIntrospectionStrategy(Introspection\StrategyInterface $introspectionStrategy)
    {
        $this->introspectionStrategy = $introspectionStrategy;
        return $this;
    }

    /**
     * @return Introspection\StrategyInterface
     */
    public function getIntrospectionStrategy()
    {
        return $this->introspectionStrategy;
    }

    /**
     * Set explicit class names
     *
     * Adding classes this way will cause the defintion to report them when getClasses() is called.
     *
     * NOTE: Starting with version 3.0 this will not prevent this definitin to create definitions
     * for unknown classes dynamically
     *
     * @todo    Should this be checking provided class names for existence?
     * @param   string[] $explicitClasses    An array of class names
     * @return  self
     */
    public function setExplicitClasses(array $explicitClasses)
    {
        $this->explicitLookups = true;

        foreach ($explicitClasses as $eClass) {
            $this->definition[$eClass] = true;
        }

        return $this;
    }

    /**
     * @param string $class
     */
    public function forceLoadClass($class)
    {
        $this->processClass($class, true);
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
        if ($this->explicitLookups && array_key_exists($class, $this->definition)) {
            return true;
        }

        return class_exists($class) || interface_exists($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassSupertypes($class)
    {
        $this->processClass($class);
        parent::getClassSupertypes($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstantiator($class)
    {
        $this->processClass($class);
        return parent::getInstantiator($class);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethods($class)
    {
        $this->processClass($class);
        return parent::hasMethods($class);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($class, $method)
    {
        $this->processClass($class);
        return parent::hasMethod($class, $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods($class)
    {
        $this->processClass($class);
        return parent::getMethods($class);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethodParameters($class, $method)
    {
        $this->processClass($class);
        return parent::hasMethodParameters($class, $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodParameters($class, $method)
    {
        $this->processClass($class);
        return parent::getMethodParameters($class, $method);
    }

    /**
     * Create the definition for the given class
     *
     * @param   string  $class      The class name to process
     * @param   bool    $forceLoad  Flag if the defintion should force reloading the class, even if the definition already exists
     * @return  self
     */
    protected function processClass($class, $forceLoad = false)
    {
        if (!isset($this->processedClass[$class]) || $this->processedClass[$class] === false) {
            $this->processedClass[$class] = (array_key_exists($class, $this->definition) && is_array($this->definition[$class]));
        }

        if (!$forceLoad && $this->processedClass[$class]) {
            return;
        }

        $strategy = $this->introspectionStrategy; // localize for readability

        /** @var $rClass \Zend\Code\Reflection\ClassReflection */
        $rClass = new Reflection\ClassReflection($class);
        $className = $rClass->getName();

        // setup the key in classes
        $this->processedClass[$className] = true;
        $this->definition[$className] = [
            'supertypes'   => [],
            'instantiator' => null,
            'methods'      => [],
            'parameters'   => []
        ];

        $def = &$this->definition[$className]; // localize for brevity
        $rTarget = $rClass;
        $supertypes = $rTarget->getInterfaceNames();

        // Build up supertype list
        while ($rTargetParent = $rTarget->getParentClass()) {
            $supertypes[] = $rTargetParent->getName();
            $supertypes = array_merge($supertypes, $rTargetParent->getInterfaceNames());
            $rTarget = $rTargetParent;
        };

        // TODO: Should we automatically create definitions for the super classes?
        $def['supertypes'] = array_keys(array_flip($supertypes));

        // If the class is instanciable default to instanciation via constructor
        if ($rClass->isInstantiable()) {
            $def['instantiator'] = '__construct';
        }

        // Check for constructor
        if ($rClass->hasMethod('__construct')) {
            $def['methods']['__construct'] = self::METHOD_IS_CONSTRUCTOR; // required
            $this->processParams($def, $rClass, $rClass->getMethod('__construct'));
        }

        $useAnnotations = $strategy->getUseAnnotations(); // localize for performannce

        foreach ($rClass->getMethods(Reflection\MethodReflection::IS_PUBLIC) as $rMethod) {
            $methodName = $rMethod->getName();

            if ($rMethod->getName() === '__construct' || (!$useAnnotations && $rMethod->isStatic())) {
                continue;
            }

            $annotations = $useAnnotations? $rMethod->getAnnotations($strategy->getAnnotationManager()) : null;

            // This condition only applies if annotations are used. This allows static methods to be defined as instanciators
            if ($rMethod->isStatic()) {
                if (!($annotations instanceof AnnotationCollection) || !$annotations->hasAnnotation(Annotation\Instantiator::class)) {
                    continue;
                }

                $def['instantiator'] = $rMethod->getName();
                $def['methods'][$rMethod->getName()] = self::METHOD_IS_CONSTRUCTOR;
                $this->processParams($def, $rClass, $rMethod);

                continue;
            }

            // Adding injection methods without parameters is quite useless (means nothing to inject)
            if (!count($rMethod->getParameters())) {
                continue;
            }

            if (($annotations instanceof AnnotationCollection) && $annotations->hasAnnotation(Annotation\Inject::class)) {
                // use '@Inject' and search for parameters
                $def['methods'][$methodName] = self::METHOD_IS_EAGER;
                $this->processParams($def, $rClass, $rMethod);
                continue;
            }

            if ($strategy->includeMethod($rMethod)) {
                $def['methods'][$methodName] = self::METHOD_IS_OPTIONAL;
                $this->processParams($def, $rClass, $rMethod);
                continue;
            }
        }

        // Check interface inclusion
        /* @var $rIface \ReflectionClass */
        foreach ($rClass->getInterfaces() as $rIface) {
            // consult the strategy
            if (!$strategy->includeInterfaceMethods($rIface)) {
                continue;
            }

            foreach ($rIface->getMethods() as $rMethod) {
                if (($rMethod->getName() == '__construct') || $rMethod->isStatic() || !count($rMethod->getParameters())) {
                    continue;
                }

                $def['methods'][$rMethod->getName()] = self::METHOD_IS_AWARE;
                $this->processParams($def, $rClass, $rMethod);
            }
        }
    }

    /**
     * Process method parameters
     *
     * @param array                                  $def
     * @param \Zend\Code\Reflection\ClassReflection  $rClass
     * @param \Zend\Code\Reflection\MethodReflection $rMethod
     */
    protected function processParams(&$def, Reflection\ClassReflection $rClass, Reflection\MethodReflection $rMethod)
    {
        if (!count($rMethod->getParameters())) {
            return;
        }

        $strategy = $this->getIntrospectionStrategy();
        $methodName = $rMethod->getName();
        $def['parameters'][$methodName] = [];

        foreach ($rMethod->getParameters() as $reflectedParameter) {
            /** @var $p \ReflectionParameter  */
            $name = $reflectedParameter->getName();
            $isOptional = ($reflectedParameter->isOptional() && $reflectedParameter->isDefaultValueAvailable());

            $parameterDefinition = new MethodParameter();
            $parameterDefinition->name = $name;
            $parameterDefinition->isRequired = !$isOptional;
            $parameterDefinition->position = $reflectedParameter->getPosition();
            $parameterDefinition->type = $strategy->reflectParameterType($reflectedParameter);
            $parameterDefinition->default = $isOptional? $reflectedParameter->getDefaultValue() : null;

            $def['parameters'][$methodName][$name] = $parameterDefinition;
        }
    }
}
