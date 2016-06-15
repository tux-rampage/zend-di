<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\CodeGenerator;


interface FactoryInterface
{
    /**
     * Create an instance
     *
     * @param array $options
     * @return object
     */
    public function create(array $options);

    /**
     * Inject dependencies into an instance
     *
     * @param   object  $instance   The instance to be injected
     */
    public function injectDependencies($instance);
}
