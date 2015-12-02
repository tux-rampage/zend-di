<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;


/**
 * Interface for implementing di service locators
 */
interface ServiceLocatorInterface
{
    /**
     * Check if the service locator can provide the given type or service name
     *
     * @return bool
     */
    public function provides($name);


    /**
     * Retrieve a service instance
     *
     * @param  string      $name   Class or service name
     * @return object|null
     */
    public function getInstance($name);
}
