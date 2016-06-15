<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Di\Resolver\DependencyResolverInterface;

/**
 * Provides a DI configuration from an array
 *
 * This configures the instanciation process of the dependency injector
 *
 * **Example:**
 * ```php
 * return [
 *     // This section provides global type preferences
 *     // Those are visited if a specific instance has no preference definions
 *     'preferences' => [
 *         // The key is the requested class or interface name, the values are
 *         // the types the dependency injector should prefer
 *         Some\Interface::class => [
 *             Some\Preference1::class,
 *             'My.AliasPreference'
 *         ],
 *     ],
 *     // This configures the instanciation of specific types
 *     // Types may also be purely virtual by defining the aliasOf key.
 *     'instances' => [
 *         My\Class::class => [
 *              // Enable or disable eager resolver mode for this type
 *              // The default is disabled
 *              'eager' => false,
 *
 *              // Enable or disable strict resolver mode for this type
 *              // The default is disabled
 *              'strict' => false,
 *
 *              'preferences' => [
 *                  // this superseds the global type preferences
 *                  // when My\Class is instanciated
 *                  Some\Interface::class => [ 'My.SpecificAlias' ]
 *              ],
 *              'injections' => [
 *                  // This section may provide injections for methods which are defined by definiton
 *                  // and arbitary methods
 *
 *                  // Methods known to DI via the DI definitions, may be configured with named parameters
 *                  '__construct' => [
 *                      'foo' => My\FooImpl::class, // Use the given type to provide the injection (depends on definition)
 *                      'bar' => '*' // Use the type preferences
 *                  ],
 *                  // Assuming setSomething is not in the DI class defintions, the values will be
 *                  // injected to the method as POSITIONAL method arguments
 *                  'setSomething' => [
 *                      'A literal String Value'
 *                  ],
 *              ]
 *         ],
 *
 *         'My.Alias' => [
 *             // typeOf defines virtual classes which can be used as type perferences or for
 *             // newInstance calls. They allow providing a different configs for a class
 *             'typeOf' => Some\Class::class,
 *             'preferences' => [
 *                  Foo::class => Bar::class
 *             ]
 *         ]
 *     ]
 * ];
 * ```
 *
 * ## Notes on Injections
 *
 * Named arguments and Automatic type lookups will only work for Methods that are known to the dependency injector
 * through its definitions. Injections for unknown methods do not perform type lookups on its own.
 *
 * A value injection without any lookups can be forced by providing a Resolver\ValueInjection instance.
 *
 * To force a service/class instance provide a Resolver\TypeInjection instance. For classes known from
 * the definitions, a type preference might be the better approach
 *
 * @see Zend\Di\Resolver\ValueInjection A container to force injection of a value
 * @see Zend\Di\Resolver\TypeInjection  A container to force looking up a specific type instance for injection
 */
class Config implements ConfigInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Construct from option array
     *
     * Utilizes the given options array or traversable.
     *
     * @param  array|Traversable    $options    The options array. Traversables
     *                                          will be converted to an array
     *                                          internally
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException(
                'Config data must be of type Traversable or an array'
            );
        }
        $this->data = $options;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::getAllInjectionMethods()
     */
    public function getAllInjectionMethods($type)
    {
        if (!isset($this->data['instances'][$type]['injections']) || !is_array($this->data['types'][$type]['injections'])) {
            return [];
        }

        return array_keys($this->data['instances'][$type]['injections']);
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::getClassForAlias()
     */
    public function getClassForAlias($name)
    {
        if (isset($this->data['instances'][$name]['aliasOf'])) {
            return $this->data['instances'][$name]['aliasOf'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::getResolverMode()
     */
    public function getResolverMode($type)
    {
        $mode = DependencyResolverInterface::RESOLVE_ESSENTIAL;

        if (isset($this->data['instances'][$type]['eager']) && $this->data['instances'][$type]['eager']) {
            $mode = $mode | DependencyResolverInterface::RESOLVE_EAGER;
        }

        if (isset($this->data['instances'][$type]['strict'])) {
            $mode = $mode | DependencyResolverInterface::RESOLVE_STRICT;
        }

        return $mode;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::getInjections()
     */
    public function getInjections($type, $method)
    {
        if (!isset($this->data['instances'][$type]['injections'][$method])) {
            return [];
        }

        return $this->data['instances'][$type]['injections'][$method];
    }

    /**
     * Returns the instanciation paramters for the given type
     *
     * @param   string  $type   The alias or class name
     * @return  array           The configured parameters
     */
    public function getParameters($type)
    {
        if (!isset($this->data['instances'][$type]['parameters']) || !is_array($this->data['instances'][$type]['parameters'])) {
            return [];
        }

        return $this->data['instances'][$type]['parameters'];
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::getTypePreferences()
     */
    public function getTypePreferences($type)
    {
        if (!isset($this->data['preferences'][$type])) {
            return [];
        }

        $preference = $this->data['preferences'][$type];
        if (!is_array($preference) && !($preference instanceof \Traversable)) {
            $preference = [$preference];
        }

        return $preference;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::getTypePreferencesForClass()
     */
    public function getTypePreferencesForClass($type, $classOrAlias)
    {
        if (!isset($this->data['instances'][$classOrAlias]['preferences'][$type])) {
            return $this->getTypePreferences($type);
        }

        $preference = $this->data['instances'][$classOrAlias]['preferences'][$type];

        if (!is_array($preference) && !($preference instanceof Traversable)) {
            $preference = [$preference];
        }

        return $preference;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\ConfigInterface::isAlias()
     */
    public function isAlias($name)
    {
        return isset($this->data['instances'][$name]['aliasOf']);
    }
}
