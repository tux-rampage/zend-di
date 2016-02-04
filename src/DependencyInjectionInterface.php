<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;


interface DependencyInjectionInterface
{
    /**
     * Resolve method policy
     *
     * EAGER: explore type preference or go through
     */
    const RESOLVE_EAGER = 1;

    /**
     * Resolve method policy
     *
     * STRICT: explore type preference or throw exception
     */
    const RESOLVE_STRICT = 2;

    /**
     * Check if this dependency injector can handle the given class
     *
     * @param   string $name
     * @return  bool
     */
    public function canInstanciate($name);

    /**
     * Retrieve a new instance of a class
     *
     * Forces retrieval of a discrete instance of the given class.
     *
     * @param  mixed   $name                   Class name or service alias
     * @param  array   $options                Parameters used for instanciation
     * @param  bool    $injectAllDependencies  Automatically inject non-instanciator dependencies as well (methods, properties).
     * @return object  The resulting instace
     * @throws Exception\ExceptionInterface When an error occours during instanciation
     */
    public function newInstance($name, array $options = [], $injectAllDependencies = true);

    /**
     * Inject non-constructor dependencies to the given instance
     *
     * @param  object  $instance  The instance to inject to
     * @param  string  $name      Optionally the alias/class to use for resolving injections
     * @throws Exception\ExceptionInterface
     */
    public function injectDependencies($instance, $name = null);
}
