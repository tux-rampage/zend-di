<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Builder;

use Zend\Di\Definition\DefinitionInterface;
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
     * @var int
     */
    protected $requirementType = DefinitionInterface::METHOD_IS_OPTIONAL;

    /**
     * @param  string|null $name
     * @return self
     */
    public function setName($name)
    {
        if ($name == '__construct') {
            return DefinitionInterface::METHOD_IS_CONSTRUCTOR;
        }

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
     * Returns the method's requirement type
     *
     * @return int
     */
    public function getRequirementType()
    {
        return $this->requirementType;
    }

    /**
     * Sets the requirement type for the resolver
     *
     * @param int $requirementType
     * @return self
     */
    public function setRequirementType($requirementType)
    {
        $this->requirementType = (int)$requirementType;
        return $this;
    }

    /**
     * Shorthand method for `setRequirementType(DefinitionInterface::METHOD_IS_REQUIRED)`
     *
     * @return self
     */
    public function setRequired()
    {
        $this->requirementType = DefinitionInterface::METHOD_IS_REQUIRED;
        return $this;
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
}

