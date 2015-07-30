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
 * Instance manager contract
 *
 * Interface for implementing instance managers used by the
 * dependency injection container.
 */
interface InstanceManagerInterface
{
    /**
     * Check the instance manager if the given service is available
     *
     * @param  string  $name  The class or alias name to check for
     * @return bool    True if the class/name exists or is instanciatable
     */
    public function has($name);

    /**
     * Returns the (possibly) shared instance for the given class or alias name
     *
     * @param  string $name  The class or alias name
     * @return object
     * @throws Exception\ExceptionInterface
     */
    public function get($name);
}