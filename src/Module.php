<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use Zend\ModuleManager\Feature\ConfigProviderInterface;


/**
 * Provides Module functionality for Zend Framework 3 applications
 *
 * To add the DI integration to your application, add it to the ZF modules list:
 *
 * ```php
 *  // application.config.php
 *  return [
 *      // ...
 *      'modules' => [
 *          'Zend\\Di',
 *          // ...
 *      ]
 *  ];
 * ```
 */
class Module implements ConfigProviderInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\ModuleManager\Feature\ConfigProviderInterface::getConfig()
     */
    public function getConfig()
    {
        return require __DIR__ . '/../config/module.dist.php';
    }
}
