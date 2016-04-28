<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Di\Definition;

use Zend\Di\Definition\BuilderDefinition;
use Zend\Di\Definition\Builder;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Di\Definition\MethodParameter;

class BuilderDefinitionTest extends TestCase
{
    public function testBuilderImplementsDefinition()
    {
        $builder = new BuilderDefinition();
        $this->assertInstanceOf('Zend\Di\Definition\DefinitionInterface', $builder);
    }

    public function testBuilderCanBuildClassWithMethods()
    {
        $class = new Builder\PhpClass();
        $class->setName('Foo');
        $class->addSuperType('Parent');

        $injectionMethod = new Builder\InjectionMethod();
        $injectionMethod->setName('injectBar');
        $injectionMethod->addParameter('bar', 'Bar');

        $class->addInjectionMethod($injectionMethod);

        $definition = new BuilderDefinition();
        $definition->addClass($class);

        $this->assertTrue($definition->hasClass('Foo'));
        $this->assertEquals('__construct', $definition->getInstantiator('Foo'));
        $this->assertContains('Parent', $definition->getClassSupertypes('Foo'));
        $this->assertTrue($definition->hasMethods('Foo'));
        $this->assertTrue($definition->hasMethod('Foo', 'injectBar'));
        $this->assertContains('injectBar', $definition->getMethods('Foo'));


        $params = $definition->getMethodParameters('Foo', 'injectBar');
        $this->assertCount(1, $params);
        $this->assertArrayHasKey('bar', $params);
        $this->assertInstanceOf(MethodParameter::class, $params['bar']);
        $this->assertEquals('bar', $params['bar']->name);
        $this->assertNull($params['bar']->default);
        $this->assertNull($params['bar']->isRequired);
        $this->assertEquals(0, $params['bar']->position);
        $this->assertEquals('Bar', $params['bar']->type);
    }

    public function testBuilderDefinitionHasMethodsThrowsRuntimeException()
    {
        $definition = new BuilderDefinition();

        $this->setExpectedException('Zend\Di\Exception\RuntimeException');
        $definition->hasMethods('Foo');
    }

    public function testBuilderDefinitionHasMethods()
    {
        $class = new Builder\PhpClass();
        $class->setName('Foo');

        $definition = new BuilderDefinition();
        $definition->addClass($class);

        $this->assertFalse($definition->hasMethods('Foo'));
        $class->createInjectionMethod('injectBar');

        $this->assertTrue($definition->hasMethods('Foo'));
    }

    public function testCanCreateClassFromFluentInterface()
    {
        $builder = new BuilderDefinition();
        $class = $builder->addClass($builder->createClass('Foo'));

        $this->assertTrue($builder->hasClass('Foo'));
    }

    public function testCanCreateInjectionMethodsAndPopulateFromFluentInterface()
    {
        $builder = new BuilderDefinition();
        $foo     = $builder->createClass('Foo');
        $foo->setName('Foo');
        $foo->createInjectionMethod('setBar')
            ->addParameter('bar', 'Bar');
        $foo->createInjectionMethod('setConfig')
            ->addParameter('config', null);

        $this->assertTrue($builder->hasClass('Foo'));
        $this->assertTrue($builder->hasMethod('Foo', 'setBar'));
        $this->assertTrue($builder->hasMethod('Foo', 'setConfig'));

        $params = $builder->getMethodParameters('Foo', 'setBar');
        $this->assertArrayHasKey('bar', $params);
        $this->assertInstanceOf(MethodParameter::class, $params['bar']);
        $this->assertEquals('bar', $params['bar']->name);
        $this->assertNull($params['bar']->default);
        $this->assertNull($params['bar']->isRequired);
        $this->assertEquals(0, $params['bar']->position);
        $this->assertEquals('Bar', $params['bar']->type);

        $params = $builder->getMethodParameters('Foo', 'setConfig');
        $this->assertArrayHasKey('config', $params);
        $this->assertInstanceOf(MethodParameter::class, $params['config']);
        $this->assertEquals('config', $params['config']->name);
        $this->assertNull($params['config']->default);
        $this->assertNull($params['config']->isRequired);
        $this->assertEquals(0, $params['config']->position);
        $this->assertNull($params['config']->type);
    }

    public function testBuilderCanSpecifyClassToUseWithCreateClass()
    {
        $builder = new BuilderDefinition();
        $this->assertEquals('Zend\Di\Definition\Builder\PhpClass', $builder->getClassBuilder());

        $builder->setClassBuilder('Foo');
        $this->assertEquals('Foo', $builder->getClassBuilder());
    }

    public function testClassBuilderCanSpecifyClassToUseWhenCreatingInjectionMethods()
    {
        $builder = new BuilderDefinition();
        $class   = $builder->createClass('Foo');

        $this->assertEquals('Zend\Di\Definition\Builder\InjectionMethod', $class->getMethodBuilder());

        $class->setMethodBuilder('Foo');
        $this->assertEquals('Foo', $class->getMethodBuilder());
    }
}
