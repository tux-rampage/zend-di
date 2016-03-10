<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Introspection;

use ReflectionParameter;

/**
 * Introspection strategy for PHP5
 */
class Php5Strategy extends DefaultStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::reflectParameterType()
     */
    public function reflectParameterType(ReflectionParameter $parameter)
    {
        $class = $parameter->getClass();

        if (!$class) {
            return null;
        }

        return $class->getName();
    }
}
