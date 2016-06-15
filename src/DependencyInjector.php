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
use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\Definition\RuntimeDefinition;


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
     * @var ContainerInterface
     */
    protected $container = null;

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
     * @var string[]
     */
    protected $instanciationStack = [];

    /**
     * @var string[]
     */
    protected $canInstanciateStack = [];

    /**
     * Constructor
     *
     * @param null|DefinitionInterface  $definitions
     * @param null|InstanceManager      $instanceManager
     * @param null|Config               $config
     */
    public function __construct(ConfigInterface $config = null, DefinitionInterface $definitions = null, DependencyResolverInterface $resolver = null, ContainerInterface $container = null)
    {
        if (!$definitions instanceof DefinitionList) {
            $definitions = new DefinitionList($definitions? : new Definition\RuntimeDefinition());
        }

        $this->definitions = $definitions;
        $this->config = $config? : new Config();
        $this->resolver = $resolver? : new DependencyResolver($this->definitions, $this->config);

        $this->setContainer($container? : new DefaultContainer($this));
    }

    /**
     * Set the ioc container
     *
     * Sets the ioc container to utilize for fetching instances of dependencies
     *
     * @param  ContainerInterface $container
     * @return self
     */
    public function setContainer(ContainerInterface $container)
    {
        if ($this->resolver) {
            $this->resolver->setContainer($container);
        }

        $this->container = $container;
        return $this;
    }

    /**
     * Returns the definition list
     *
     * @return \Zend\Di\DefinitionList
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * Returns the resolver
     *
     * @return \Zend\Di\Resolver\DependencyResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Returns the configuration
     *
     * @return \Zend\Di\ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns the class name for the requested type
     *
     * @param string $type
     */
    private function getClassName($type)
    {
        if ($this->config->isAlias($type)) {
            return $this->config->getClassForAlias($type);
        }

        return $type;
    }

    /**
     * Returns the actual instanciation class name
     *
     * @todo Implement subscriber subscribers
     * @param string $class
     * @return string
     */
    private function mapInstanciateClassName($class)
    {
        return $class;
    }

    /**
     * Check if the given type name can be instanciated
     *
     * This will be the case if the name points to a class.
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

        return (class_exists($class) && !interface_exists($class));
    }

    /**
     * Retrieve a new instance of a class
     *
     * Forces retrieval of a discrete instance of the given class, using the optionally provided
     * constructor parameters.
     *
     * @param  mixed                            $name               Class name or service alias
     * @param  array                            $parameters         Constructor paramters
     * @return object|null
     * @throws Exception\ClassNotFoundException
     * @throws Exception\RuntimeException
     */
    public function newInstance($name, array $parameters = [])
    {
        // Minimize cycle failures
        if ($this->currentInstances[$name] && count($this->instanciationStack)) {
            return $this->currentInstances[$name];
        }

        if (in_array($name, $this->instanciationStack)) {
            throw new Exception\CircularDependencyException(sprintf('Circular dependency: %s -> %s', implode(' -> ', $this->instanciationStack), $name));
        }

        $this->instanciationStack[] = $name;

        try {
            $instance = $this->createInstance($name, $parameters);
            $this->currentInstances[$name] = $instance;

            $this->doInjectDependencies($instance, $name);
        } catch (Exception\ExceptionInterface $e) {
            $this->instanciationStack = [];
            $this->currentInstances = [];
            throw $e;
        }

        array_pop($this->instanciationStack);

        if (!count($this->instanciationStack)) {
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
        if (!$type) {
            $type = get_class($instance);
        }

        $this->doInjectDependencies($instance, $type);
    }

    /**
     * Perform injections into the given instance
     *
     * @param  object $instance     The instance to inject the dependencies to
     * @param  string $type         The typename to use for resolving the dependencies
     * @return self
     */
    protected function doInjectDependencies($instance, $type)
    {
        $class = $this->getClassName($type);
        $definitions = $this->definitions;
        $visitedMethods = [];

        if (null !== ($instanciator = $definitions->getInstantiator($class))) {
            $visitedMethods[] = $instanciator;
        }

        foreach ($this->config->getAllInjectionMethods($type) as $method) {
            if (in_array($method, $visitedMethods)) {
                continue;
            }

            $this->resolveAndCallInjectionMethodForInstance($instance, $method, $class, $type);
            $visitedMethods[] = $method;
        }
    }

    /**
     * Resolves dependencies for the the method by using the instance type and injects them into the provided instance
     *
     * @param  object                       $instance
     * @param  string                       $method
     * @param  string|null                  $instanceClass
     * @param  string|null                  $instanceType
     * @throws Exception\RuntimeException
     */
    private function resolveAndCallInjectionMethodForInstance($instance, $method, $instanceClass, $instanceType)
    {
        $params = $this->resolveMethodParameters($instanceType, $method);

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
     * Any parameters provided will be used as constructor/instanciator arguments only.
     *
     * @param   string  $name   The type name to instanciate
     * @param   array   $params Constructor/instanciator arguments
     * @return  object
     *
     * @throws  Exception\InvalidCallbackException
     * @throws  Exception\ClassNotFoundException
     */
    protected function createInstance($name, $params)
    {
        // localize dependencies
        $definitions = $this->definitions;
        $class = $this->getClassName($name);

        if (!$definitions->hasClass($class)) {
            $aliasMsg = ($name != $class) ? ' (specified by alias ' . $name . ')' : '';
            throw new Exception\ClassNotFoundException('Class ' . $class . $aliasMsg . ' could not be located in provided definitions.');
        }

        $instanciator = $definitions->getInstantiator($class);
        $callParameters = [];
        $class = $this->mapInstanciateClassName($class? : $name);

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
            $callParameters = $this->resolveMethodParameters($name, $instanciator, $params);
        }

        if ($instanciator !== '__construct') {
            if (!is_callable([$class, $instanciator])) {
                throw new Exception\InvalidCallbackException(sprintf('The instanciator "%s" is not callable on the requested class "%s"'), $instanciator, $class);
            }

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
     * Resolve parameters
     *
     * At first this method utilizes the resolver to obtain the types to inject.
     * If this was successful (the resolver returned a non-null value), it will use
     * the ioc container to fetch the instances
     *
     * @param  string                                $type      The class or alias name to resolve for
     * @param  string                                $method    The method name to resolve
     * @param  array                                 $params    Provided call time parameters
     * @param  bool                                  $required  Override resolver requirements
     * @throws Exception\UndefinedReferenceException            When a type cannot be obtained via the ioc container and the method is required for injection
     * @throws Exception\CircularDependencyException            When a circular dependency is detected
     * @return array|null                                       The resulting arguments in call order or null if nothing could be obtained
     */
    private function resolveMethodParameters($type, $method, array $params = [])
    {
        $container = $this->container;
        $resolved = $this->resolver->resolveMethodParameters($type, $method, $params);
        $params = [];

        if ($resolved === null) {
            return null;
        }

        $class = $this->getClassName($type);
        $mode = $this->definitions->getResolverMode($class);
        $requirement = $this->definitions->getMethodRequirementType($class, $method);
        $isRequired = (($mode & $requirement) != 0);

        foreach ($resolved as $position => $arg) {
            if ($arg instanceof Resolver\ValueInjection) {
                $params[] = $arg->getValue();
                continue;
            } else if ($arg === null) {
                $params[] = null;
                continue;
            }

            if ($arg instanceof Resolver\TypeInjection) {
                $arg = $arg->getType();
            }

            if (!$container->has($arg)) {
                if (!$isRequired) {
                    return null;
                }

                throw new Exception\UndefinedReferenceException('Could not obtain instance ' . $arg . ' from ioc container for parameter ' . $position . ' of method ' . $type . '::' . $method);
            }

            $params[] = $container->get($arg);
        }

        return $params;
    }
}
