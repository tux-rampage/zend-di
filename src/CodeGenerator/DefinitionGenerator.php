<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;

use Zend\Di\Definition\Compiler as DefinitionCompiler;
use Zend\Di\Definition\ArrayDefinition;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;

/**
 * Compiles a Di definition class using the defintion compiler
 */
class DefinitionGenerator
{
    use GeneratorTrait;

    /**
     * @var string
     */
    private $namespace = 'Zend\Di\Generated';

    /**
     * @var DefinitionCompiler
     */
    private $compiler;

    /**
     * @param DefinitionCompiler $compiler
     */
    public function __construct( $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Generate the definition class
     */
    public function generate()
    {
        $this->ensureOutputDirectory();
        $this->ensureDirectory($this->outputDirectory . '/src');

        $this->compiler->toFile($this->outputDirectory . '/di-definition.php');

        $constructBody = 'parent::__construct(require __DIR__ . \'/../di-definition.php\');';

        $class = new ClassGenerator('Definition', $this->namespace);
        $class->setExtendedClass('\\' . ArrayDefinition::class)
            ->addMethod('__construct', [], MethodGenerator::FLAG_PUBLIC, $constructBody)
            ->setDocBlock(new DocBlockGenerator('GENERATED DI DEFINITION'));

        $file = new FileGenerator();
        $file->setFilename($this->outputDirectory . '/src/Definition.php')
            ->setDocBlock('GENERATED DI DEFINITION')
            ->setNamespace($class->getNamespaceName())
            ->setClass($class)
            ->write();
    }
}