<?php
/**
 * @author    Axel Helmert <ah@luka.de>
 * @license   LUKA Proprietary
 * @copyright Copyright (c) 2016 LUKA netconsult GmbH (www.luka.de)
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
