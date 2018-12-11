<?php
/**
 * @see       https://github.com/zendframework/zend-di for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-di/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Di\CodeGenerator;

use PHPUnit\Framework\TestCase;
use Zend\Di\CodeGenerator\FactoryGenerator;
use Zend\Di\Config;
use Zend\Di\Definition\RuntimeDefinition;
use Zend\Di\Resolver\DependencyResolver;
use ZendTest\Di\TestAsset;

/**
 * FactoryGenerator test case.
 */
class FactoryGeneratorTest extends TestCase
{
    const DEFAULT_NAMESPACE = 'ZendTest\Di\Generated\Factory';

    use GeneratorTestTrait;

    public function testGenerateCreatesFiles()
    {
        $config = new Config();
        $resolver = new DependencyResolver(new RuntimeDefinition(), $config);
        $generator = new FactoryGenerator($config, $resolver, self::DEFAULT_NAMESPACE);

        $generator->setOutputDirectory($this->dir . '/Factory');
        $generator->generate(TestAsset\RequiresA::class);

        $this->assertFileExists($this->dir . '/Factory/ZendTest/Di/TestAsset/RequiresAFactory.php');
    }

    public function testGenerateBuildsUpClassMap()
    {
        $config = new Config();
        $resolver = new DependencyResolver(new RuntimeDefinition(), $config);
        $generator = new FactoryGenerator($config, $resolver, self::DEFAULT_NAMESPACE);

        $generator->setOutputDirectory($this->dir . '/FactoryMultiple');

        $f1 = $generator->generate(TestAsset\RequiresA::class);
        $f2 = $generator->generate(TestAsset\Constructor\EmptyConstructor::class);

        $expected = [
            $f1 => str_replace('\\', '/', TestAsset\RequiresA::class) . 'Factory.php',
            $f2 => str_replace('\\', '/', TestAsset\Constructor\EmptyConstructor::class) . 'Factory.php',
        ];

        $this->assertEquals($expected, $generator->getClassmap());
    }

    public function testGenerateForClassWithoutParams()
    {
        $config = new Config();
        $resolver = new DependencyResolver(new RuntimeDefinition(), $config);
        $generator = new FactoryGenerator($config, $resolver, self::DEFAULT_NAMESPACE);

        $generator->setOutputDirectory($this->dir . '/Factory');
        $generator->generate(TestAsset\A::class);

        $this->assertFileEquals(
            __DIR__ . '/../_files/expected-codegen-results/factories/without-params.php',
            $this->dir . '/Factory/ZendTest/Di/TestAsset/AFactory.php'
        );
    }

    public function testGenerateForClassWithParams()
    {
        $config = new Config();
        $resolver = new DependencyResolver(new RuntimeDefinition(), $config);
        $generator = new FactoryGenerator($config, $resolver, self::DEFAULT_NAMESPACE);

        $generator->setOutputDirectory($this->dir . '/Factory');
        $generator->generate(TestAsset\Constructor\MixedArguments::class);

        $this->assertFileEquals(
            __DIR__ . '/../_files/expected-codegen-results/factories/with-params.php',
            $this->dir . '/Factory/ZendTest/Di/TestAsset/Constructor/MixedArgumentsFactory.php'
        );
    }
}
