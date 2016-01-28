<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use Closure;
use Zend\Di\Exception\RuntimeException as DiRuntimeException;
use Zend\ServiceManager\Exception\ExceptionInterface as ServiceManagerException;
use Zend\Di\Resolver\DependencyResolver;
use Zend\Di\Resolver\DependencyResolverInterface;
use Interop\Container\ContainerInterface;

/**
 * Dependency injector that can generate instances using class definitions and configured instance parameters
 */
class Di implements DependencyInjectionInterface
{
    /**
     * @var DefinitionList
     */
    protected $definitions = null;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    /**
     * @var DependencyResolverInterface
     */
    protected $resolver;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * All already created instances in the current newInstance() process
     *
     * @var array
     */
    protected $currentInstances = [];

    /**
     * @var string
     */
    protected $instanceContext = [];

    /**
     * All the class dependencies [source][dependency]
     *
     * @var array
     */
    protected $currentDependencies = [];

    /**
     * All the dependenent aliases
     *
     * @var array
     */
    protected $currentAliasDependenencies = [];

    /**
     * All the class references [dependency][source]
     *
     * @var array
     */
    protected $references = [];

    /**
     * Resolve method policy
     *
     * EAGER: explore type preference or go through
     */
    const RESOLVE_EAGER = 1;

    /**
     * Resolve method policy
     *
     * STRICT: explore type preference or throw exception
     */
    const RESOLVE_STRICT = 2;

    /**
     * use only specified parameters
     */
    const METHOD_IS_OPTIONAL = 0;

    /**
     * resolve mode RESOLVE_EAGER | RESOLVE_STRICT
     */
    const METHOD_IS_REQUIRED = 3;

    /**
     * resolve mode RESOLVE_EAGER
     */
    const METHOD_IS_EAGER = 1;

    /**
     * Constructor
     *
     * @param null|DefinitionList  $definitions
     * @param null|InstanceManager $instanceManager
     * @param null|Config   $config
     */
    public function __construct(ConfigInterface $config = null, DefinitionList $definitions = null, DependencyResolverInterface $resolver = null, ContainerInterface $serviceLocator = null)
    {
        $this->definitions = $definitions? : new DefinitionList(new Definition\RuntimeDefinition());
        $this->config = $config? : new Config();
        $this->resolver = $resolver? : new DependencyResolver($this->definitions, $this->config);

        $this->setServiceLocator($serviceLocator? : new ServiceLocator($this));
    }

    /**
     * Set the service locator
     *
     * @param  ContainerInterface $serviceLocator
     * @return self
     */
    public function setServiceLocator(ContainerInterface $serviceLocator)
    {
        if ($serviceLocator instanceof DependencyInjectionAwareInterface) {
            $serviceLocator->setDependencyInjector($this);
        }

        if ($this->resolver) {
            $this->resolver->setServiceLocator($serviceLocator);
        }

        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * Returns the class name for the requested type
     *
     * This abstraction allows to exchange the requested class name with a proxy class
     *
     * @todo  Implement subscriber support
     * @param string $type
     */
    protected function getClassName($type)
    {
        if ($this->config->isAlias($type)) {
            return $this->config->getClassForAlias($type);
        }

        return $type;
    }

    /**
     * Retrieve a new instance of a class
     *
     * Forces retrieval of a discrete instance of the given class, using the optionally provided
     * constructor parameters.
     *
     * @param  mixed                            $name                   Class name or service alias
     * @param  array                            $parameters             Constructor paramters
     * @param  bool                             $injectAllDependencies  Set to false to only inject construction dependencis
     * @return object|null
     * @throws Exception\ClassNotFoundException
     * @throws Exception\RuntimeException
     */
    public function newInstance($name, array $parameters = [], $injectAllDependencies = true)
    {
        // localize dependencies
        $definitions = $this->definitions;
        $class = $this->getClassName($name);

        // Minimize cycle failures
        if ($this->currentInstances[$name] && !count($this->instanceContext)) {
            return $this->currentInstances[$name];
        }

        array_push($this->instanceContext, ['NEW', $class, $name]);

        if (!$definitions->hasClass($class)) {
            $aliasMsg = ($name != $class) ? ' (specified by alias ' . $name . ')' : '';
            throw new Exception\ClassNotFoundException(
                'Class ' . $class . $aliasMsg . ' could not be located in provided definitions.'
                );
        }

        $instantiator     = $definitions->getInstantiator($class);
        $injectionMethods = [];
        $injectionMethods[$class] = $definitions->getMethods($class);

        foreach ($definitions->getClassSupertypes($class) as $supertype) {
            $injectionMethods[$supertype] = $definitions->getMethods($supertype);
        }

        try {
            $instance = $this->createInstance($name, $instantiator, $parameters, $class);
        } catch (\Exception $e) {
            $this->instanceContext = [];
            $this->currentInstances = [];
            throw $e;
        }

        $this->currentInstances[$name] = $instance;
        array_pop($this->instanceContext);

        if ($injectAllDependencies) {
            $this->doInjectDependencies($instance, $class, $name, count($this->instanceContext));
        }

        if (!count($this->instanceContext)) {
            $this->currentInstances = [];
        }

        return $instance;
    }

    /**
     * Inject dependencies
     *
     * @param  object $instance  The target instance to inject dependencies to
     * @param  string $type      The typename to use for resolving the dependencies
     * @return void
     */
    public function injectDependencies($instance, $type = null)
    {
        $class = get_class($instance);
        if (!$type) {
            $type = $class;
        }

        $this->doInjectDependencies($instance, $class, $type);
    }

    /**
     * @param unknown $instance
     * @param unknown $class
     * @param unknown $type
     * @param unknown $allowDelay
     * @return self
     */
    protected function doInjectDependencies($instance, $class, $type, $allowDelay = false)
    {
        array_push($this->instanceContext, ['INJECT', $class, $type]);

        $class = $this->getClassName($type);
        $definitions = $this->definitions;
        $visitedMethods = [];

        if (null !== ($instanciator = $definitions->getInstantiator($class))) {
            $visitedMethods[] = $instanciator;
        }

        foreach ($definitions->getMethods($type) as $method) {
            if (in_array($method, $visitedMethods)) {
                continue;
            }

            $this->handleInjectDependency($instance, $method, $class, $type, $allowDelay);
            $visitedMethods[] = $method;
        }

        foreach ($definitions->getClassSupertypes($class) as $superType) {
            foreach ($definitions->getMethods($superType) as $method) {
                if (in_array($method, $visitedMethods)) {
                    continue;
                }

                $this->handleInjectDependency($instance, $method, $class, $type, $allowDelay);
                $visitedMethods[] = $method;
            }
        }
    }

    /**
     * @todo Refactor
     * @param object      $instance
     * @param array       $injectionMethods
     * @param array       $params
     * @param string|null $instanceClass
     * @param string|null$instanceAlias
     * @param  string                     $requestedName
     * @throws Exception\RuntimeException
     */
    protected function handleInjectDependency($instance, $method, $instanceClass, $instanceType, $allowDelay = false)
    {
        // localize dependencies
        $definitions = $this->definitions;
        $container   = $this->serviceLocator;

        try {
            $params = $this->resolveMethodParameters($instanceType, $method);

            if ($params == null) {
                return;
            }

            call_user_func_array([$instance, $method], $params);
        } catch (Exception\ExceptionInterface $e) {
            // TODO: Delay?
        }


        //FIXME: GO ON!
        $calledMethods = ['__construct' => true];

        if ($injectionMethods) {
            foreach ($injectionMethods as $type => $typeInjectionMethods) {
                foreach ($typeInjectionMethods as $typeInjectionMethod => $methodRequirementType) {
                    if (!isset($calledMethods[$typeInjectionMethod])) {
                        if ($this->resolveAndCallInjectionMethodForInstance($instance, $typeInjectionMethod, $params, $instanceAlias, $methodRequirementType, $type)) {
                            $calledMethods[$typeInjectionMethod] = true;
                        }
                    }
                }
            }

            if ($requestedName) {
                $instanceConfig = $instanceManager->getConfig($requestedName);

                if ($instanceConfig['injections']) {
                    $objectsToInject = $methodsToCall = [];
                    foreach ($instanceConfig['injections'] as $injectName => $injectValue) {
                        if (is_int($injectName) && is_string($injectValue)) {
                            $objectsToInject[] = $this->get($injectValue, $params);
                        } elseif (is_string($injectName) && is_array($injectValue)) {
                            if (is_string(key($injectValue))) {
                                $methodsToCall[] = ['method' => $injectName, 'args' => $injectValue];
                            } else {
                                foreach ($injectValue as $methodCallArgs) {
                                    $methodsToCall[] = ['method' => $injectName, 'args' => $methodCallArgs];
                                }
                            }
                        } elseif (is_object($injectValue)) {
                            $objectsToInject[] = $injectValue;
                        } elseif (is_int($injectName) && is_array($injectValue)) {
                            throw new Exception\RuntimeException(
                                'An injection was provided with a keyed index and an array of data, try using'
                                . ' the name of a particular method as a key for your injection data.'
                                );
                        }
                    }
                    if ($objectsToInject) {
                        foreach ($objectsToInject as $objectToInject) {
                            $calledMethods = ['__construct' => true];
                            foreach ($injectionMethods as $type => $typeInjectionMethods) {
                                foreach ($typeInjectionMethods as $typeInjectionMethod => $methodRequirementType) {
                                    if (!isset($calledMethods[$typeInjectionMethod])) {
                                        $methodParams = $definitions->getMethodParameters($type, $typeInjectionMethod);
                                        if ($methodParams) {
                                            foreach ($methodParams as $methodParam) {
                                                $objectToInjectClass = $this->getClass($objectToInject);
                                                if ($objectToInjectClass == $methodParam[1] || is_subclass_of($objectToInjectClass, $methodParam[1])) {
                                                    if ($this->resolveAndCallInjectionMethodForInstance($instance, $typeInjectionMethod, [$methodParam[0] => $objectToInject], $instanceAlias, self::METHOD_IS_REQUIRED, $type)) {
                                                        $calledMethods[$typeInjectionMethod] = true;
                                                    }
                                                    continue 3;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($methodsToCall) {
                        foreach ($methodsToCall as $methodInfo) {
                            $this->resolveAndCallInjectionMethodForInstance($instance, $methodInfo['method'], $methodInfo['args'], $instanceAlias, self::METHOD_IS_REQUIRED, $instanceClass);
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieve a class instance based on class name
     *
     * Any parameters provided will be used as constructor arguments.
     *
     * @param  string      $class
     * @param  string      $instanciator
     * @param  array       $params
     * @param  string|null $class
     * @return object
     */
    protected function createInstance($name, $instanciator, $params, $class = null)
    {
        $callParameters = $params;
        $class = $class? : $name;

        if (!class_exists($class)) {
            if (interface_exists($class)) {
                throw new Exception\ClassNotFoundException(sprintf(
                    'Cannot instantiate interface "%s"',
                    $class
                    ));
            }

            throw new Exception\ClassNotFoundException(sprintf(
                'Class "%s" does not exist; cannot instantiate',
                $class
                ));
        }

        if ($this->definitions->hasMethod($name, $instanciator)) {
            $callParameters = $this->resolveMethodParameters($name, $instanciator, $params, self::METHOD_IS_INSTANTIATOR);
        }

        // Hack to avoid Reflection in most common use cases
        switch (count($callParameters)) {
            case 0:
                return new $class();
            case 1:
                return new $class($callParameters[0]);
            case 2:
                return new $class($callParameters[0], $callParameters[1]);
            case 3:
                return new $class($callParameters[0], $callParameters[1], $callParameters[2]);
            default:
                $r = new \ReflectionClass($class);
                return $r->newInstanceArgs($callParameters);
        }
    }

    /**
     * This parameter will handle any injection methods and resolution of
     * dependencies for such methods
     *
     * @param  object      $instance
     * @param  string      $method
     * @param  array       $params
     * @param  string      $alias
     * @param  bool        $methodRequirementType
     * @param  string|null $methodClass
     * @return bool
     */
    protected function resolveAndCallInjectionMethodForInstance($instance, $method, $params, $alias, $methodRequirementType, $methodClass = null)
    {
        $methodClass = ($methodClass) ?: $this->getClass($instance);
        $callParameters = $this->resolveMethodParameters($methodClass, $method, $params, $alias, $methodRequirementType);
        if ($callParameters == false) {
            return false;
        }
        if ($callParameters !== array_fill(0, count($callParameters), null)) {
            call_user_func_array([$instance, $method], $callParameters);

            return true;
        }

        return false;
    }

    /**
     * Resolve parameters referencing other services
     *
     * @param  string                                $class
     * @param  string                                $method
     * @param  array                                 $params
     * @param  int                                   $methodRequirementType
     * @throws Exception\MissingPropertyException
     * @throws Exception\CircularDependencyException
     * @return array|null
     */
    protected function resolveMethodParameters($class, $method, array $params = [], $methodRequirementType = self::METHOD_IS_OPTIONAL)
    {
        $container = $this->serviceLocator;
        $resolved = $this->resolver->resolveMethodParameters($class, $method, $params);
        $params = [];

        foreach ($resolved as $position => $arg) {
            if ($arg instanceof Resolver\ValueInjection) {
                $params[] = $arg->getValue();
                continue;
            }

            if (($arg === null) || !$container->has($arg)) {
                if ($methodRequirementType & self::METHOD_IS_REQUIRED) {
                    throw new Exception\MissingPropertyException('Missing property for parameter ' . $position . ' of method ' . $class . '::' . $method);
                }

                return null;
            }

            $params[] = $container->get($arg);
        }

        return $params;
    }
}
