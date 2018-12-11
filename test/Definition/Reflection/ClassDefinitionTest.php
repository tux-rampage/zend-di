<?php
/**
 * @see       https://github.com/zendframework/zend-di for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-di/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Di\Definition\Reflection;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zend\Di\Definition\ParameterInterface;
use Zend\Di\Definition\Reflection\ClassDefinition;
use ZendTest\Di\TestAsset\Constructor as ConstructorAsset;
use ZendTest\Di\TestAsset\Hierarchy as HierarchyAsset;

/**
 * @coversDefaultClass Zend\Di\Definition\Reflection\ClassDefinition
 */
class ClassDefinitionTest extends TestCase
{
    public function testGetReflection()
    {
        $result = (new ClassDefinition(HierarchyAsset\A::class))->getReflection();

        $this->assertInstanceOf(ReflectionClass::class, $result);
        $this->assertEquals(HierarchyAsset\A::class, $result->getName());
    }

    public function testGetSupertypesReturnsAllClasses()
    {
        $supertypes = (new ClassDefinition(HierarchyAsset\C::class))->getSupertypes();
        $expected = [
            HierarchyAsset\A::class,
            HierarchyAsset\B::class,
        ];

        $this->assertInternalType('array', $supertypes);

        sort($expected);
        sort($supertypes);

        $this->assertEquals($expected, $supertypes);
    }

    public function testGetSupertypesReturnsEmptyArray()
    {
        $supertypes = (new ClassDefinition(HierarchyAsset\A::class))->getSupertypes();

        $this->assertInternalType('array', $supertypes);
        $this->assertEmpty($supertypes);
    }

    /**
     * Tests ClassDefinition->getInterfaces()
     */
    public function testGetInterfacesReturnsAllInterfaces()
    {
        $result = (new ClassDefinition(HierarchyAsset\C::class))->getInterfaces();
        $expected = [
            HierarchyAsset\InterfaceA::class,
            HierarchyAsset\InterfaceB::class,
            HierarchyAsset\InterfaceC::class,
        ];

        $this->assertInternalType('array', $result);

        sort($result);
        sort($expected);

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests ClassDefinition->getInterfaces()
     */
    public function testGetInterfacesReturnsArray()
    {
        $result = (new ClassDefinition(HierarchyAsset\A::class))->getInterfaces();

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    public function provideClassesWithParameters()
    {
        return [
            'optional' => [ConstructorAsset\OptionalArguments::class, 2],
            'required' => [ConstructorAsset\RequiredArguments::class, 3],
        ];
    }

    /**
     * @dataProvider provideClassesWithParameters
     */
    public function testGetParametersReturnsAllParameters($class, $expectedItemCount)
    {
        $result = (new ClassDefinition($class))->getParameters();

        $this->assertInternalType('array', $result);
        $this->assertCount($expectedItemCount, $result);
        $this->assertContainsOnlyInstancesOf(ParameterInterface::class, $result);
    }

    public function testGetParametersWithScalarTypehints()
    {
        $result = (new ClassDefinition(ConstructorAsset\Php7::class))->getParameters();

        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ParameterInterface::class, $result);
    }

    public function provideParameterlessClasses()
    {
        return [
            'noargs'      => [ConstructorAsset\EmptyConstructor::class],
            'noconstruct' => [ConstructorAsset\NoConstructor::class],
        ];
    }

    /**
     * @dataProvider provideParameterlessClasses
     */
    public function testGetParametersReturnsAnArray($class)
    {
        $result = (new ClassDefinition($class))->getParameters();
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }
}
