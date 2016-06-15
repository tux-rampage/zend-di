($type = $this->resolvePreference($injection, $requestedType)<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Resolver;

use Zend\Di\Exception\InvalidArgumentException;


/**
 * Wrapper for types that should be looked up for injection
 */
class TypeInjection
{
    /**
     * Holds the type name to look uo
     *
     * @var string
     */
    protected $type;

    /**
     * Constructor
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = (string)$type;
    }

    /**
     * Allows to export this class with var_export
     *
     * @param   array                       $state  The state representation of this class as array
     * @throws  InvalidArgumentException            Thrown if the state is incomplete
     * @return  TypeInjection                       The resulting instance
     */
    public static function __set_state(array $state)
    {
        if (!isset($state['type'])) {
            throw new InvalidArgumentException('Missing type name in __set_state() export.');
        }

        return new static($state['type']);
    }

    /**
     * Get the type name to look up for injection
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Simply converts to the type name string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->type;
    }
}
