<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Annotation;

use Zend\Code\Annotation\AnnotationInterface;

/**
 * Class annotation for defining the resolver mode
 */
class Mode implements AnnotationInterface
{
    /**
     * @var string
     */
    public $mode = null;

    /**
     * {@inheritDoc}
     */
    public function initialize($mode)
    {
        $this->mode = $mode;
    }
}
