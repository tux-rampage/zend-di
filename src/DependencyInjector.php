<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use Interop\Container\ContainerInterface;

use Zend\Di\Resolver\DependencyResolver;
use Zend\Di\Resolver\DependencyResolverInterface;


/**
 * Dependency injector that can generate instances using class definitions and configured instance parameters
 */
class DependencyInjector implements DependencyInjectionInterface
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
     * Secondary method injections that are allowed to omit
     *
     * @var \SplQueue
     */
    protected $delayedInjections;

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
        $this->delayedInjections = new \SplQueue();
        $this->setServiceLocator($serviceLocator? : new ServiceLocator($this));

        $this->delayedInjections->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
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
     * Check if the given type name can be instanciated
     *
     * @param  string $name
     * @return bool
     * @see    \Zend\Di\DependencyInjectionInterface::canInstanciate()
     */
    public function canInstanciate($name)
    {
        $class = $name;

        if ($this->config->isAlias($name)) {
            $class = $this->config->getClassForAlias($name);
        }

        return class_exists($class);
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

        try {
            $instance = $this->createInstance($name, $instantiator, $parameters, $class);
        } catch (Exception\ExceptionInterface $e) {
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
     * Inject dependencies that could not be resolved during the new instance process
     */
    protected function injectDelayedDependencies()
    {
        foreach ($this->delayedInjections as $args) {
            list($instance, $method, $instanceClass, $instanceType) = $args;
            $this->resolveAndCallInjectionMethodForInstance($instance, $method, $instanceClass, $instanceType);
        }

        return $this;
    }

    /**
     * Perform injections into the given instance
     *
     * @param  object $instance     The instance to inject the dependencies to
     * @param  string $class        The class name of this instance
     * @param  string $type         The typename to use for resolving the dependencies
     * @param  bool   $allowDelay   Flag if the injections for methods that fail to resolve are allowed to be delayed (within nested newInstance calls)
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

            if (!$this->resolveAndCallInjectionMethodForInstance($instance, $method, $class, $type) && $allowDelay) {
                $this->delayedInjections->push([$instance, $method, $class, $type]);
            }

            $visitedMethods[] = $method;
        }

        foreach ($definitions->getClassSupertypes($class) as $superType) {
            foreach ($definitions->getMethods($superType) as $method) {
                if (in_array($method, $visitedMethods)) {
                    continue;
                }

                if (!$this->resolveAndCallInjectionMethodForInstance($instance, $method, $class, $type) && $allowDelay) {
                    $this->delayedInjections->push([$instance, $method, $class, $type]);
                }

                $visitedMethods[] = $method;
            }
        }

        foreach ($this->config->getAllInjectionMethods($type) as $method) {
            if (in_array($method, $visitedMethods)) {
                continue;
            }

            if (!$this->resolveAndCallInjectionMethodForInstance($instance, $method, $class, $type) && $allowDelay) {
                $this->delayedInjections->push([$instance, $method, $class, $type]);
            }

            $visitedMethods[] = $method;
        }
    }

    /**
     * Resolves dependencies for the the method by using the instance type and injects them into the provided instance
     *
     * @param  object                       $instance
     * @param  array                        $method
     * @param  string|null                  $instanceClass
     * @param  string|null                  $instanceType
     * @throws Exception\RuntimeException
     */
    protected function resolveAndCallInjectionMethodForInstance($instance, $method, $instanceClass, $instanceType)
    {
        $params = $this->resolveMethodParameters($instanceType, $method, [], self::METHOD_IS_OPTIONAL);

        if ($params == null) {
            return false;
        }

        // Do not call a method without parameters
        if (count($params)) {
            call_user_func_array([$instance, $method], $params);
        }

        return true;
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
            throw new Exception\ClassNotFoundException(sprintf(
                'Class "%s" does not exist; cannot instantiate',
                $class
            ));
        }

        if (interface_exists($class)) {
            throw new Exception\ClassNotFoundException(sprintf(
                'Cannot instantiate interface "%s"',
                $class
            ));
        }

        if ($this->definitions->hasMethod($class, $instanciator)) {
            $callParameters = $this->resolveMethodParameters($name, $instanciator, $params, true);
        }

        if ($instanciator !== '__construct') {
            return call_user_func_array([$class, $instanciator], $callParameters);
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
     * Resolve parameters referencing other services
     *
     * @param  string                                $type      The class or alias name
     * @param  string                                $method    The method name to resolve
     * @param  array                                 $params    Provided call time parameters
     * @param  bool                                  $required  Override resolver requirements
     * @throws Exception\MissingPropertyException
     * @throws Exception\CircularDependencyException
     * @return array|null
     */
    protected function resolveMethodParameters($type, $method, array $params = [], $required = false)
    {
        $container = $this->serviceLocator;
        $resolved = $this->resolver->resolveMethodParameters($type, $method, $params);
        $params = [];

        if ($resolved === null) {
            if ($required) {
                throw new Exception\MissingPropertyException('Could not resolve required parameters for ' . $type . '::' . $method);
            }

            return null;
        }

        foreach ($resolved as $position => $arg) {
            if ($arg instanceof Resolver\ValueInjection) {
                $params[] = $arg->getValue();
                continue;
            }

            if (($arg === null) || !$container->has($arg)) {
                if ($required) {
                    throw new Exception\MissingPropertyException('Missing property for parameter ' . $position . ' of method ' . $type . '::' . $method);
                }

                return null;
            }

            $params[] = $container->get($arg);
        }

        return $params;
    }
}
