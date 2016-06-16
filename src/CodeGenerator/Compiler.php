<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;

use Zend\Code\Scanner\FileScanner;
use Zend\Di\Definition\Compiler as DefinitionCompiler;
use Zend\Di\DependencyInjector;
use Zend\Di\ConfigInterface;
use Zend\Di\Resolver\DependencyResolverInterface;


/**
 * Generates optimized dependency injector classes
 *
 * This class will aggregate the code generators to compile
 * optimized dependency injector classes.
 *
 * It will create the following classes, that will bypass the
 * runtime dependency resolvers and class analyzers whenever possible:
 *
 *  * `Zend\Di\Generated\DependencyInjector`
 *  * `Zend\Di\Generated\Definition`
 *
 * The compiler will store the classes psr-4 (To namespace `Zend\Di\Generated`) compatible in the `src/`
 * directory of the output directory. This allows you to register it as psr-4 autoload
 * in your composer.json (Note that `composer dump-autoload -o` can be executed after compilation to gain additional performance improvements).
 */
class Compiler
{
    use GeneratorTrait;

    /**
     * @var DefinitionCompiler
     */
    protected $definitionCompiler;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DependencyResolverInterface
     */
    protected $resolver;

    /**
     * Constructs the compiler instance
     *
     * @param ConfigInterface $config
     * @param DependencyResolverInterface $resolver
     */
    public function __construct(ConfigInterface $config, DependencyResolverInterface $resolver)
    {
        $this->config = $config;
        $this->resolver = $resolver;
        $this->definitionCompiler = new DefinitionCompiler();
    }

    /**
     * Creates a compiler from the given injector instance
     *
     * @param DependencyInjector $injector
     * @return Compiler
     */
    public static function fromInjectorInstance(DependencyInjector $injector)
    {
        return new static($injector->getConfig(), $injector->getResolver());
    }

    /**
     * Add a directory to scan
     *
     * @param   string  $directory  The path to the directory to scan
     * @return  self                Provides a fluent interface
     */
    public function addSourceDirectory($directory)
    {
        $this->definitionCompiler->addDirectory($directory);
        return $this;
    }

    /**
     * Add a single source file
     *
     * @param   string  $file   The path to the php file
     * @return  self            Provides a fluent interface
     */
    public function addSourceFile($file)
    {
        $this->definitionCompiler->addCodeScannerFile(new FileScanner($file));
        return $this;
    }

    /**
     * Perform code generation
     *
     * Generate all optimized classes and write the resulting files to the output directory.
     */
    public function compile()
    {
        $this->ensureOutputDirectory();

        $this->definitionCompiler->compile();

        $definitionGenerator = new DefinitionGenerator($this->definitionCompiler);
        $injectorGenerator = new DependencyInjectorGenerator($this->config, $this->definitionCompiler->getDefinion(), $this->resolver);

        $definitionGenerator->generate();
        $injectorGenerator->generate();

        return $this;
    }
}
