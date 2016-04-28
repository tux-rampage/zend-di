<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Di\Definition;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Di\Definition\RuntimeDefinition;
use Zend\Di\Definition\MethodParameter;

class RuntimeDefinitionTest extends TestCase
{
    /**
     * @group ZF2-308
     */
    public function testStaticMethodsNotIncludedInDefinitions()
    {
        $definition = new RuntimeDefinition;
        $this->assertTrue($definition->hasMethod('ZendTest\Di\TestAsset\SetterInjection\StaticSetter', 'setFoo'));
        $this->assertFalse($definition->hasMethod('ZendTest\Di\TestAsset\SetterInjection\StaticSetter', 'setName'));
    }

    public function testIncludesDefaultMethodParameters()
    {
        $definition = new RuntimeDefinition();

        $definition->forceLoadClass('ZendTest\Di\TestAsset\ConstructorInjection\OptionalParameters');
        $parameters = $definition->getMethodParameters(
            'ZendTest\Di\TestAsset\ConstructorInjection\OptionalParameters',
            '__construct'
        );

        $expectedParams = [
            [
                'a',
                null,
                false,
                null,
            ],
            [
                'b',
                null,
                false,
                'defaultConstruct',
            ],
            [
                'c',
                null,
                false,
                [],
            ],
        ];

        foreach ($expectedParams as $position => $expected) {
            /* @var $actual MethodParameter */
            $actual = array_shift($parameters);
            $this->assertEquals($position, $actual->position);
            $this->assertEquals($expected[0], $actual->name);
            $this->assertEquals($expected[1], $actual->type);
            $this->assertEquals($expected[2], $actual->isRequired);
            $this->assertEquals($expected[3], $actual->default);
        }
    }

    public function testExceptionDefaultValue()
    {
        $definition = new RuntimeDefinition();

        $definition->forceLoadClass('RecursiveIteratorIterator');

        $parameters = $definition->getMethodParameters('RecursiveIteratorIterator', '__construct');
        $expectedParams = [
            [
                'iterator',
                'Traversable',
                true,
                null,
            ],
            [
                'mode',
                null,
                true,
                null,
            ],
            [
                'flags',
                null,
                true,
                null,
            ],
        ];

        foreach ($expectedParams as $position => $expected) {
            /* @var $actual MethodParameter */
            $actual = array_shift($parameters);
            $this->assertEquals($position, $actual->position);
            $this->assertEquals($expected[0], $actual->name);
            $this->assertEquals($expected[1], $actual->type);
            $this->assertEquals($expected[2], $actual->isRequired);
            $this->assertEquals($expected[3], $actual->default);
        }
    }

    /**
     * Test if methods from aware interfaces without params are excluded
     */
    public function testExcludeAwareMethodsWithoutParameters()
    {
        $definition = new RuntimeDefinition();
        $this->assertTrue($definition->hasMethod('ZendTest\Di\TestAsset\AwareClasses\B', 'setSomething'));
        $this->assertFalse($definition->hasMethod('ZendTest\Di\TestAsset\AwareClasses\B', 'getSomething'));
    }

    /**
     * Test if methods without params are excluded
     */
    public function testExcludeMethodsWithoutParameters()
    {
        $definition = new RuntimeDefinition();
        $this->assertFalse($definition->hasMethod('ZendTest\Di\TestAsset\SetterInjection\NoParamsSetter', 'setFoo'));
    }


    /**
     * Test to see if we can introspect explicit classes
     */
    public function testExplicitClassesStillGetProccessedByIntrospectionStrategy()
    {
        $className = 'ZendTest\Di\TestAsset\ConstructorInjection\OptionalParameters';
        $explicitClasses = [$className => true];
        $definition = new RuntimeDefinition(null, $explicitClasses);

        $this->assertTrue($definition->hasClass($className));
        $this->assertSame(["__construct"=> 3], $definition->getMethods($className));
    }
}
