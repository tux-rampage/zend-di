<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Di\Resolver;

use Zend\Di\Resolver\DependencyResolver;
use Zend\Di\ConfigInterface;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Di\Definition\DefinitionInterface;


/**
 * @coversDefaultClass Zend\Di\Resolver\DependencyResolver
 */
class DependencyResolverTest extends TestCase
{
    /**
     * @return array
     */
    public function globalTypePreferenceTestDataProvider()
    {
        return [
            [
                'SomeInterface', // type
                null, // asAliasOfClass
                'preferences' => [
                    'Some\SpecificClass'
                ],
                'definition' => [
                    'Some\SpecificClass' => [
                        'SomeInterface'
                    ]
                ],
                'Some\SpecificClass'
            ]
        ];
    }

    /**
     * Tests the resolve preference code for specific configurations and definitions
     *
     * @dataProvider globalTypePreferenceTestDataProvider
     * @covers ::resolvePreference
     *
     * @param string $type
     * @param string $asAliasOfClass
     * @param array  $preferences
     * @param array  $definition
     * @param string $expectedClass
     */
    public function testResolveConfiguredGlobalPreference($type, $asAliasOfClass, $preferences, $definition, $expectedClass)
    {
        $configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $configMock->method('getTypePreferences')->willReturn($preferences);
        $configMock->method('isAlias')->willReturn($asAliasOfClass !== null);
        $configMock->method('getClassForAlias')->willReturn($asAliasOfClass);

        $definitionMock = $this->getMockForAbstractClass(DefinitionInterface::class);
        $definitionMock->method('hasClass')->willReturnCallback(function($class) use ($definition) {
            return isset($definition[$class]);
        });

        $definitionMock->method('getClassSupertypes')->willReturnCallback(function($class) use ($definition) {
            return $definition[$class];
        });


        $resolver = new DependencyResolver($definitionMock, $configMock);
        $this->assertEquals($expectedClass, $resolver->resolvePreference($type));
    }
}