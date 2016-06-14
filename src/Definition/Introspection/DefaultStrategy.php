<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition\Introspection;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

use Zend\Di\Definition\Annotation;

use Zend\Code\Annotation\AnnotationManager;
use Zend\Code\Annotation\Parser\GenericAnnotationParser;


/**
 * The default introspection strategy for PHP7 and up
 */
class DefaultStrategy implements StrategyInterface
{
    /**
     * Method inclusion pattern
     */
    const METHOD_PATTERN = '/^set[A-Z]{1}\w*/';

    /**
     * Interface inclusion pattern
     */
    const INTERFACE_PATTERN = '/\w*Aware\w*/';


    /**
     * The annotation manager instance for this strategy
     *
     * @var AnnotationManager
     */
    protected $annotationManager = null;

    /**
     * Annotation usage flag
     *
     * @var bool
     */
    protected $useAnnotations = true;

    /**
     * Constructor
     *
     * @param null|AnnotationManager $annotationManager
     */
    public function __construct(AnnotationManager $annotationManager = null)
    {
        $this->annotationManager = $annotationManager?: $this->createDefaultAnnotationManager();
    }

    /**
     * Creates the default annotation manager
     *
     * @return AnnotationManager
     */
    protected function createDefaultAnnotationManager()
    {
        $annotationManager = new AnnotationManager();
        $parser            = new GenericAnnotationParser();

        $parser->registerAnnotation(new Annotation\Inject());
        $parser->registerAnnotation(new Annotation\Instantiator());
        $parser->registerAnnotation(new Annotation\Mode());

        $annotationManager->attach($parser);

        return $annotationManager;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::getAnnotationManager()
     */
    public function getAnnotationManager()
    {
        return $this->annotationManager;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::getUseAnnotations()
     */
    public function getUseAnnotations()
    {
        return $this->useAnnotations;
    }

    /**
     * Specify annotation usage
     *
     * @param   bool    $flag   Whether to enable/disable annotation usage
     * @return  self
     */
    public function setUseAnnotations($flag)
    {
        $this->useAnnotations = (bool)$flag;
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::includeInterfaceMethods()
     */
    public function includeInterfaceMethods(ReflectionClass $reflectedInterface)
    {
        return (bool)preg_match(self::INTERFACE_PATTERN, $reflectedInterface->getName());
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::includeMethod()
     */
    public function includeMethod(ReflectionMethod $reflectedMethod)
    {
        return (bool)preg_match(self::METHOD_PATTERN, $reflectedMethod->getName());
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\Introspection\StrategyInterface::reflectParameterType()
     */
    public function reflectParameterType(ReflectionParameter $parameter)
    {
        if (!$parameter->hasType()) {
            return null;
        }

        return (string)$parameter->getType();
    }
}
