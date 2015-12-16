<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Resolver;

use Zend\Di\Exception;


/**
 * Wrapper for values that should be directly injected
 */
class ValueInjection
{
    /**
     * Holds the value to inject
     *
     * @var mixed
     */
    protected $value;

    /**
     * @param mixed $value
     */
    public function __class($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value to inject
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     * @throws Exception\UnexportableValueException
     */
    public function export()
    {
        return var_export($this->value, true);
    }

    /**
     * Checks wether the value can be exported for code generation or not
     *
     * @return bool
     */
    public function isExportable()
    {
        if (is_scalar($this->value)) {
            return true;
        }

        if (is_object($this->value) && method_exists($this->value, '__set_state')) {
            return true;
        }

        return false;
    }
}