<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Introspection;

/**
 * The default introspection strategy for PHP7 and up
 */
class DefaultStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::reflectParameterType()
     */
    public function reflectParameterType(\ReflectionParameter $parameter)
    {
        // TODO Auto-generated method stub
    }
}
