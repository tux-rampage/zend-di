<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Builder;

use Zend\Di\Di;
use Zend\Di\Definition\MethodParameter;

/**
 * Definitions for an injection endpoint method
 */
class InjectionMethod
{
    /**
     * @var string|null
     */
    protected $name = null;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @param  string|null $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string          $name
     * @param  string|null     $type
     * @param  mixed|null      $isRequired
     * @param  mixed|null      $default
     * @return InjectionMethod
     */
    public function addParameter($name, $type = null, $isRequired = null, $default = null)
    {
        $definition = new MethodParameter();
        $definition->name = $name;
        $definition->type = $type;
        $definition->isRequired = $isRequired;
        $definition->default = $default;
        $definition->position = count($this->parameters);

        $this->parameters[$name] = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @deprecated
     * @param mixed $requirement
     * @return int
     */
    public static function detectMethodRequirement($requirement)
    {
        return 0;
    }
}
