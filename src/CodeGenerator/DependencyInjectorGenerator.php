<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Di\ConfigInterface;
use Zend\Di\DependencyInjector;
use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\Resolver\DependencyResolverInterface;
use Zend\Di\Resolver\ValueInjection;
use Zend\Di\Resolver\TypeInjection;
use Zend\Di\Exception\GenerateCodeException;

/**
 * Generator for the depenendency injector
 *
 * Generates a DependencyInjector class that will use a generated factory for
 * a requested type, if available. This factory will contained pre-resolved
 * dependencies from the provided configuration, definition and resolver instances.
 *
 * The generated factories are considered private to the generated dependency injector
 * and are not intented to be used or referenced outside of this class. Therefore they are
 * not designed to be autoloadable.
 */
class DependencyInjectorGenerator
{
    use GeneratorTrait;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DependencyResolverInterface
     */
    protected $resolver;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var int
     */
    private $factoryIndex = 0;

    /**
     * @var string
     */
    private $namespace = 'Zend\Di\Generated';

    /**
     * Constructs the compiler instance
     *
     * @param   ConfigInterface             $config     The configuration to compile from
     * @param   DefinitionInterface         $definition The definition to compile from
     * @param   DependencyResolverInterface $resolver   The resolver to utilize
     */
    public function __construct(ConfigInterface $config, DefinitionInterface $definition, DependencyResolverInterface $resolver)
    {
        $this->config = $config;
        $this->definition = $definition;
        $this->resolver = $resolver;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getClassName($type)
    {
        if ($this->config->isAlias($type)) {
            return $this->config->getClassForAlias($type);
        }

        return $type;
    }

    /**
     * Builds the code for method parameters
     *
     * @param   string  $type           The type name to build for
     * @param   string  $method         The method name
     * @param   bool    $acceptParams   True if the generated method accepts params
     * @return  array|null              An array containing the param list and the code segment or null if
     *                                  The method was optional but not resolvable
     */
    private function buildMethodParametersCode($type, $method, $acceptParams = false)
    {
        $class = $this->getClassName($type);
        $params = $this->resolver->resolveMethodParameters($type, $method);

        if ($params === null) {
            return null;
        }

        $params = array_values($params); // Ensure numerical indexes
        $counter = 0; // Ensure numerical indexes
        $args = []; // Multi dimensional array containing the code segments for conditional and unconditional assignments
        $names = [];
        $code = '';
        $intention = 4;
        $tab = str_repeat(' ', $intention);

        foreach ($this->definition->getMethodParameters($class, $method) as $param) {
            $arg = '$p' . ++$counter;
            $injection = array_shift($params);

            if ($injection instanceof ValueInjection) {
                // A value should be injected - this is only possible to generate
                // if this value is exportable

                if (!$injection->isExportable()) {
                    // There is a configured injection the contains an unexportable type (i.e. a resource)
                    // This can not be represented as php code - therefore the whoe method should not be generated
                    throw new GenerateCodeException(sprintf(
                        'Value injection for parameter %s in %s::%s() is not exportable (Requested type %s)',
                        $param->name, $class, $method, $type
                    ));
                }

                $code = $injection->export();
            } else {
                // Everything else is considered a type injection
                // We'll use the ioc container

                if ($injection instanceof TypeInjection) {
                    $injection = $injection->getType();
                }

                if ($type == '') {
                    // Somehow the resolver returned an empty type which is not wrapped in a ValueInjection
                    // A bug in the resolver? Anyhow this is not acceptable and the factory cannot be created
                    throw new GenerateCodeException(sprintf(
                        'Resolved injection for parameter %s in %s::%s() resulted in an empty type (Requested type %s)',
                        $param->name, $class, $method, $type
                    ));
                }

                $code = '$this->container->get(' . var_export((string)$type) . ')';
            }

            // build for two cases:
            // 1. Parameters are passed at call time
            // 2. No Parameters were passed at call time (might be slightly faster)
            // TODO: Would array_key_exists be a better alternative than isset to force a null value?
            $names[] = $arg;
            $args['u'][] = sprintf('%s = %s;', $arg, $code);
            $args['c'][] = sprintf('%1$s = array_key_exists(%3$s, $params)? $params[%3$s] : %2$s;', $arg, $code, var_export((string)$param->name));
        }

        if ($counter > 0) {
            if ($acceptParams) {
                // Build conditional initializer code:
                // If no $params were provided ignore it completely
                // otherwise check if there is a value for each dependency in $params.
                $code = 'if (empty($params)) {' . "\n"
                      . $tab . implode("\n$tab", $args['c']) . "\n"
                      . '} else {' . "\n"
                      . $tab . implode("\n$tab", $args['u']) . "\n}\n\n";
            } else {
                // The method does not accept a $params array
                // Just build the initialzer code
                implode("\n", $args['u']) . "\n\n";
            }
        }

        return [ $names, $code ];
    }

    /**
     * @param string $type
     * @return string|false
     */
    private function buildCreateMethodBody($type)
    {
        $class = $this->getClassName($type);
        $instanciator = $this->definition->getInstantiator($class)? : '__construct';
        $paramsCode = $this->buildMethodParametersCode($type, $instanciator, true);

        // The resolver was unable to deliver and somehow the instanciator
        // was not considered a requirement. Whatever caused this, it's not acceptable here
        if (!$paramsCode) {
            throw new GenerateCodeException(sprintf('Failed to resolve instanciator parameters of type %s', $type));
        }

        // Decide if new or static method call should be used
        $absoluteClassName = '\\' . $class;
        $invokeCode = sprintf(
            ($instanciator != '__construct')? '%s::%s(%s)' : 'new %1$s(%3$s)',
            $absoluteClassName,
            $instanciator,
            implode(', ', $paramsCode[0])
        );

        $code = $paramsCode[1] . '$instance = ' . $invokeCode . ";\n"
              . '$this->injectDependencies($instance);' . "\n"
              . 'return $instance;';

        return $code;
    }

    /**
     * Build injection method code
     *
     * @param string $type
     * @param string $method
     * @return string
     */
    private function buildInjectionMethod(ClassGenerator $generator, $type, $method)
    {
        $paramsCode = $this->buildMethodParametersCode($type, $method);

        // The resolver could not provide the deps and considered the
        // injection as optional - so just skip this method
        if (!$paramsCode) {
            return null;
        }

        $injectionMethod = 'doInject' . ucfirst($method);
        $code = $paramsCode[1] . '$instance->' . $method . '('
              . implode(', ', $paramsCode[0]) . ');';

        $generator->addMethod($injectionMethod, [new ParameterGenerator('$instance')], MethodGenerator::FLAG_PRIVATE, $code);

        return $injectionMethod;
    }

    /**
     * Build injector methods
     *
     * @param   ClassGenerator  $generator  The class generator to generate to
     * @param   string          $type       The type name to generate for
     * @param   string[]        $methods    The method names to generate for
     * @param   string[]        $visited    Already visited methods
     * @return  string[]                    An array with the generated injector names
     */
    private function buildInjectionMethods(ClassGenerator $generator, $type, array $methods, array &$visited)
    {
        $injectors = [];

        foreach ($methods as $method) {
            if (in_array($method, $visited)) {
                continue;
            }

            $visited[] = $method;
            $name = $this->buildInjectionMethod($generator, $type, $method);

            if ($name) {
                $injectors[] = $name;
            }
        }

        return $injectors;
    }

    /**
     * Attempts to generate a factory for the given type
     *
     * This may fail with exceptions if not all required dependencies could be resolved
     * or the configuration contains values that are not code generatable
     *
     * @param   string  $type   The type name (class or alias) to generate the factory for
     * @return  string          The resulting factory file name (without path)
     * @throws  \Exception      When the factory cannot be generated
     */
    private function generateTypeFactory($type)
    {
        $filename = 'TypeFactory' . ($this->factoryIndex++) . '.php';
        $name = 'TypeFactory_' . trim(str_replace('.', '_', $type), '\\');
        $generator = new ClassGenerator($name, $this->namespace . '\\___TF');
        $comment = 'GENERATED: Factory for ' . $type . "\nWARNING: DO NOT RELY ON THIS CLASS IN YOUR CODE - NAMES MAY CHANGE AT ANY TIME!";
        $class = $this->getClassName($type);
        $instanciator = $this->definition->getInstantiator($class)? : '__construct';
        $visited = [ $instanciator ];
        $createBody = $this->buildCreateMethodBody($type);
        $injectBody = '';

        if ($instanciator != '__construct') {
            $visited[] = '__construct';
        }

        $injectors = $this->buildInjectionMethods($generator, $type, $this->config->getAllInjectionMethods($type), $visited);

        if (($this->config->getResolverMode($type) & DependencyInjector::MODE_IS_EAGER) != 0) {
            $injectors = array_merge($injectors, $this->buildInjectionMethods($generator, $type, $this->definition->getMethods($class), $visited));
        }

        foreach ($injectors as $injectMethod) {
            $injectBody .= '$this->' . $injectMethod . '($instance);' . "\n";
        }

        $generator->setExtendedClass('\\' . AbstractFactory::class);
        $generator->setDocBlock(new DocBlockGenerator($comment));
        $generator->setFinal(true);
        $generator->addMethod('create', [new ParameterGenerator('$params', 'array')], MethodGenerator::FLAG_PUBLIC, $createBody);
        $generator->addMethod('injectDependencies', ['$instance'], MethodGenerator::FLAG_PUBLIC, $injectBody);

        $file = new FileGenerator();
        $fqClassName = '\\' . $generator->getNamespaceName() . '\\' . $generator->getName();

        $file->setFilename($this->outputDirectory . '/factories/' . $filename)
            ->setDocBlock($comment)
            ->setNamespace($generator->getNamespaceName())
            ->setClass($generator)
            ->setBody('return ' . var_export($fqClassName) . ';')
            ->write();

        return $filename;
    }

    /**
     * Generate injector
     *
     * @param array $factories
     */
    private function generateInjector(array $factories)
    {
        $listFile = new FileGenerator();
        $listFile->setFilename($this->outputDirectory . '/generated-di-factories.php')
            ->setDocBlock('AUTO GENERATED FACTORY LIST')
            ->setBody('return ' . var_export($factories, true) . ';');

        $body = 'return require __DIR__ . \'/../generated-di-factories.php\';';
        $class = new ClassGenerator('DependencyInjector', $this->namespace);
        $classFile = new FileGenerator();

        $class->setExtendedClass('\\' . AbstractDependencyInjector::class)
            ->addMethod('getFactoryList', [], MethodGenerator::FLAG_PUBLIC, $body);

        $classFile->setFilename($this->outputDirectory . '/src/DependencyInjector.php')
            ->setDocBlock('AUTO GENERATED DEPENDENCY INJECTOR')
            ->setNamespace($class->getNamespaceName())
            ->setClass($class);

        $listFile->write();
        $classFile->write();
    }

    /**
     * Generate the injector
     *
     * This will generate the injector and its factories into the output directory
     */
    public function generate()
    {
        $this->ensureOutputDirectory();
        $this->ensureDirectory($this->outputDirectory . '/src');
        $this->ensureDirectory($this->outputDirectory . '/factories');

        $factories = [];

        foreach ($this->definition->getClasses() as $class) {
            if (isset($factories[$class])) {
                continue;
            }

            try {
                $factory = $this->generateTypeFactory($class);

                if ($factory) {
                    $factories[$class] = '../factories/' . $factory;
                }
            } catch (\Exception $e) {
                // TODO: logging/notifying ...
            }
        }

        $this->generateInjector($factories);
    }
}
