<?php
/**
 * @author    Axel Helmert <ah@luka.de>
 * @license   LUKA Proprietary
 * @copyright Copyright (c) 2016 LUKA netconsult GmbH (www.luka.de)
 */

namespace ZendTest\Di\Definition\Introspection;

use ReflectionClass;
use PHPUnit_Framework_TestCase as TestCase;

use Zend\Di\Definition\Introspection\Php5Strategy;
use ZendTest\Di\TestAsset\AggregatedParamClass;
use ZendTest\Di\TestAsset\AggregateClasses\ItemInterface;
use ZendTest\Di\TestAsset\OptionalArg;

/**
 * @coversDefaultClass Zend\Di\Definition\Introspection\Php5Strategy
 */
class Php5StrategyTest extends TestCase
{
    /**
     * Tests the php 5 specific paramter typehint reflection
     *
     * @covers ::reflectParameterType()
     */
    public function testTypehintedParameterReflectionReturnsFQCN()
    {
        $strategy = new Php5Strategy();
        $assetParams = (new ReflectionClass(AggregatedParamClass::class))->getMethod('__construct')->getParameters();
        $result = $strategy->reflectParameterType($assetParams[0]);

        $this->assertEquals(ItemInterface::class, $result, 'Expected the parameters fully qualified typehinted class name');
    }

    /**
     * A parameter that is not typehinted must return null
     *
     * @covers ::reflectParameterType()
     */
    public function testUnhintedParameterReflectionRetrunsNull()
    {
        $strategy = new Php5Strategy();
        $assetParams = (new ReflectionClass(OptionalArg::class))->getMethod('__construct')->getParameters();
        $result = $strategy->reflectParameterType($assetParams[0]);

        $this->assertNull($result);
    }
}
