<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Di;

use Zend\Di\ServiceLocator;
use Zend\Di\DependencyInjectionInterface;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test the service locator
 */
class ServiceLocatorTest extends TestCase
{
    /**
     * Dependency injector mock
     *
     * @var \PHPUnit_Framework_MockObject_MockObject|DependencyInjectionInterface
     */
    private $diMock;

    /**
     * Service locator instance to test
     *
     * @var ServiceLocator
     */
    private $services;

    /**
     * Testcase setup
     */
    protected function setUp()
    {
        $this->diMock = $this->getMockForAbstractClass(DependencyInjectionInterface::class);
        $this->services = new ServiceLocator($this->diMock);
    }

    public function testRetrievingWillUseDiInstance()
    {
        $this->diMock->expects($this->atLeastOnce())
            ->method('newInstance')
            ->with('foo')
            ->willReturn(null);

        $this->assertNull($this->services->get('foo'));
    }

    public function testCanRetrievePreviouslyRegisteredServices()
    {
        $s = new \stdClass;
        $this->services->setInstance('foo', $s);
        $test = $this->services->get('foo');
        $this->assertSame($s, $test);
    }

    public function testRegisteringAServiceUnderAnExistingNameOverwrites()
    {
        $s = new \stdClass();
        $t = new \stdClass();
        $this->services->setInstance('foo', $s);
        $this->services->setInstance('foo', $t);
        $test = $this->services->get('foo');
        $this->assertSame($t, $test);
    }

    public function testRetrievingAServiceMultipleTimesReturnsSameInstance()
    {
        $s = new \stdClass();
        $this->services->setInstance('foo', $s);
        $test1 = $this->services->get('foo');
        $test2 = $this->services->get('foo');
        $this->assertSame($s, $test1);
        $this->assertSame($s, $test2);
        $this->assertSame($test1, $test2);
    }

    public function testHasUsesDiInstanceIfLocatorDoesNotKnowOfService()
    {
        $this->diMock->expects($this->atLeastOnce())
            ->method('canInstanciate')
            ->willReturn(false);

        $this->assertFalse($this->services->has('does-not-exist'));
    }

    public function testHasReturnsTrueIfLocatorKnowsOfService()
    {
        $this->services->setInstance('foo', new \stdClass());
        $this->assertTrue($this->services->has('foo'));
    }
}
