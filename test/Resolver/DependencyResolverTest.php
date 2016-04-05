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
use Zend\Di\Config;

use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\Definition\BuilderDefinition;

use PHPUnit_Framework_TestCase as TestCase;


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
        $definition = new BuilderDefinition();

        $definition
            ->addClass(
                $definition->createClass('Some\SpecificClass')
                    ->addSuperType('SomeInterface')
            )
            ->addClass($definition->createClass('NotSomeInterface'))
            ->addClass(
                $definition->createClass('Some\OtherSpecificClass')
                    ->addSuperType('SomeInterface')
            );

        return [
            [
                'SomeInterface',
                'Some\SpecificClass',
                $definition,
                new Config([
                    'preferences' => [
                        'SomeInterface' => [ 'Some\SpecificClass' ]
                    ],
                ])
            ],
            [
                'SomeInterface',
                'Some\SpecificClass',
                $definition,
                new Config([
                    'preferences' => [
                        'SomeInterface' => [ 'NotSomeInterface', 'Some\SpecificClass' ]
                    ],
                ])
            ],
            [
                'SomeInterface',
                'my.alias',
                $definition,
                new Config([
                    'preferences' => [
                        'SomeInterface' => [ 'my.alias' ]
                    ],
                    'instances' => [
                        'my.alias' => [
                            'aliasOf' => 'SomeInterface',
                        ]
                    ]
                ]),
            ],
        ];
    }

    /**
     * @return array
     */
    public function contextTypePreferenceTestDataProvider()
    {
        $definition = new BuilderDefinition();

        $definition
            ->addClass(
                $definition->createClass('Some\SpecificClass')
                    ->addSuperType('SomeInterface')
            )
            ->addClass($definition->createClass('NotSomeInterface'))
            ->addClass(
                $definition->createClass('Some\OtherSpecificClass')
                    ->addSuperType('SomeInterface')
                );

            return [
                [
                    'SomeInterface',
                    'ContextClass',
                    'Some\SpecificClass',
                    $definition,
                    new Config([
                        'instances' => [
                            'ContextClass' =>[
                                'preferences' => [
                                    'SomeInterface' => [ 'Some\SpecificClass' ]
                                ],
                            ]
                        ]
                    ])
                ],
                [
                    'SomeInterface',
                    'ContextClassB',
                    'Some\SpecificClass',
                    $definition,
                    new Config([
                        'instances' => [
                            'ContextClassB' =>[
                                'preferences' => [
                                    'SomeInterface' => [ 'NotSomeInterface', 'Some\SpecificClass' ]
                                ],
                            ]
                        ]
                    ])
                ],
                [
                    'SomeInterface',
                    'ContextClassC',
                    'my.alias',
                    $definition,
                    new Config([
                        'instances' => [
                            'my.alias' => [
                                'aliasOf' => 'SomeInterface',
                            ],
                            'ContextClassC' => [
                                'preferences' => [
                                    'SomeInterface' => [ 'my.alias' ]
                                ],
                            ]
                        ]
                    ]),
                ],
                [
                    'SomeInterface',
                    'ContextClassE',
                    'Some\OtherSpecificClass',
                    $definition,
                    new Config([
                        'preferences' => [
                            'SomeInterface' => [ 'Some\SpecificClass' ]
                        ],
                        'instances' => [
                            'ContextClassE' => [
                                'preferences' => [
                                    'SomeInterface' => [ 'Some\OtherSpecificClass' ]
                                ],
                            ]
                        ]
                    ]),
                ],
                [
                    'SomeInterface',
                    'ContextClassE',
                    'Some\SpecificClass',
                    $definition,
                    new Config([
                        'preferences' => [
                            'SomeInterface' => [ 'Some\SpecificClass' ]
                        ],
                        'instances' => [
                            'ContextClassE' => [
                                'preferences' => [
                                    'SomeInterface' => [ 'NotSomeInterface' ]
                                ],
                            ]
                        ]
                    ]),

                ],
            ];
    }

    /**
     * Tests the resolve global type preference code
     *
     * @dataProvider    globalTypePreferenceTestDataProvider
     * @covers          ::resolvePreference
     * @uses            Zend\Di\Defintion\BuilderDefinition
     * @uses            Zend\Di\Config
     *
     * @param string                $type       The type to resolve for
     * @param string                $expected   The expected preference
     * @param DefinitionInterface   $definition The definition to utilize
     * @param ConfigInterface       $config     The config to utilize
     */
    public function testResolveConfiguredGlobalPreference($type, $expected, DefinitionInterface $definition, ConfigInterface $config)
    {
        $resolver = new DependencyResolver($definition, $config);
        $this->assertEquals($expected, $resolver->resolvePreference($type));
    }

    /**
     * Tests the resolve context based type preference code
     *
     * @dataProvider    contextTypePreferenceTestDataProvider
     * @covers          ::resolvePreference
     * @uses            Zend\Di\Defintion\BuilderDefinition
     * @uses            Zend\Di\Config
     *
     * @param string                $type           The type to resolve for
     * @param string                $contextType    The context type name
     * @param string                $expected       The expected preference
     * @param DefinitionInterface   $definition     The definition to utilize
     * @param ConfigInterface       $config         The config to utilize
     */
    public function testResolveConfiguredPreferenceForContextType($type, $contextType, $expected, DefinitionInterface $definition, ConfigInterface $config)
    {
        $resolver = new DependencyResolver($definition, $config);
        $this->assertEquals($expected, $resolver->resolvePreference($type, $contextType));
    }
}