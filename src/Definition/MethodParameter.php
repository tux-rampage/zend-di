<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition;

/**
 * This class specifies a method parameter for the di definition
 */
class MethodParameter
{
    /**
     * The parameter name
     *
     * @var string
     */
    public $name;

    /**
     * The parameter position
     *
     * @var int
     */
    public $position;

    /**
     * The type name of this parameter
     *
     * @var string
     */
    public $type;

    /**
     * Contains the default value for this parameter
     *
     * @var mixed
     */
    public $default;

    /**
     * Specifies wether the parameter is required or not
     *
     * @var bool
     */
    public $isRequired;

    /**
     * @param array $values
     */
    public function __construct($values = [])
    {
        $this->name = (isset($values['name']))? $values['name'] : null;
        $this->position = (isset($values['position']))? $values['position'] : null;
        $this->type = (isset($values['type']))? $values['type'] : null;
        $this->default = (isset($values['default']))? $values['default'] : null;
        $this->isRequired = (isset($values['isRequired']))? $values['isRequired'] : null;
    }

    /**
     * Support var export
     *
     * @param array $state
     */
    public static function __set_state($state)
    {
        return new static($state);
    }
}