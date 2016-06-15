<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;

use Zend\Di\ConfigInterface;
use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\Resolver\DependencyResolverInterface;
use Zend\Di\Exception\LogicException;
use Zend\Di\Exception\RuntimeException;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;

/**
 * Generator for the depenendency injector
 */
class DependencyInjectorGenerator
{
    /**
     * @var int
     */
    protected $mode = 0755;

    /**
     * @var string
     */
    protected $outputDirectory = null;

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

    protected function ensureOutputDirectory()
    {
        if (!$this->outputDirectory) {
            throw new LogicException('Cannot generate code without output directory');
        }

        if (!is_dir($this->outputDirectory) && !mkdir($this->outputDirectory, $this->mode, true)) {
            throw new RuntimeException('Could not create output directory: ' . $this->outputDirectory);
        }
    }

    /**
     * Set the output directory
     *
     * You should configure a psr-4 autoloader with the namespace `Zend\Di\Generated`
     * to this directory.
     *
     * The compiler will attempt to create this directory if it does not exist
     *
     * @param   string  $dir    The path to the output directory
     * @param   int     $mode   The creation mode for the directory
     * @return  self            Provides a fluent interface
     */
    public function setOutputDirectory($dir, $mode = null)
    {
        $this->outputDirectory = $dir;

        if ($mode !== null) {
            $this->mode = $mode;
        }

        return $this;
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

    private function buildCreateMethodBody($type)
    {
        $class = $this->getClassName($type);
        $instanciator = $this->definition->getInstantiator($class)? : '__construct';
        $params = $this->resolver->resolveMethodParameters($type, $instanciator);

        if ($params === null) {
            return false;
        }

        foreach ($this->definition->getMethodParameters($class, $instanciator) as $param) {
            // FIXME: Complete generator
        }
    }

    /**
     * @param string $type
     * @return string|bool
     */
    private function generateTypeFactory($type)
    {
        $name = 'TypeFactory' . $this->factoryIndex++;
        $generator = new ClassGenerator($name, $this->namespace);
        $docBlock = new DocBlockGenerator('Generated factory for ' . $type);

        $generator->setExtendedClass(AbstractFactory::class);
        $generator->setDocBlock($docBlock);

        $createBody = $this->buildCreateMethodBody($type);

        if ($createBody === false) {
            return false;
        }

        // FIXME: Complete generator

        return $name;
    }

    private function generateInjector(array $factories)
    {
        $comment = "/* AUTO GENERATED FACTORY LIST */\n";
        file_put_contents($this->outputDirectory . '/generated-di-factories.php', '<?php ' . $comment . 'return ' . var_export($factories, true) . ';');

        $body = 'return require __DIR__ . \'/generated-di-factories.php\';';
        $class = new ClassGenerator('DependencyInjector', $this->namespace);
        $class->addUse(AbstractDependencyInjector::class)
            ->setExtendedClass('AbstractDependencyInjector')
            ->addMethod('getFactoryList', [], MethodGenerator::FLAG_PUBLIC, $body);

        file_put_contents($this->outputDirectory . '/DependencyInjector.php', "<?php\n\n" . $class->generate());
    }

    /**
     * Generate the injector
     */
    public function generate()
    {
        $this->ensureOutputDirectory();

        $factories = [];

        foreach ($this->definition->getClasses() as $class) {
            if (isset($factories[$class])) {
                continue;
            }

            $factory = $this->generateTypeFactory($class);

            if ($factory) {
                $factories[$class] = $factory;
            }
        }

        $this->generateInjector($factories);
    }
}
