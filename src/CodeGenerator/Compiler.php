<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;

use Zend\Di\Definition\Compiler as DefintionCompiler;
use Zend\Di\DependencyInjector;


class Compiler
{
    use GeneratorTrait;

    protected $definitionCompiler;

    protected $dependencyInjector;

    public function __construct(DependencyInjector $dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;
        $this->definitionCompiler = new DefintionCompiler();
    }

    public function compile()
    {
        $definitionGenerator = new DefinitionGenerator($this->definitionCompiler);
        $injectorGenerator = new DependencyInjectorGenerator($this->dependencyInjector->getConfig(), $this->definitionCompiler->getDefinion(), $this->dependencyInjector->getResolver());

        $definitionGenerator->generate();
        $injectorGenerator->generate();
    }
}