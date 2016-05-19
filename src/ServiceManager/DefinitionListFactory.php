<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\ServiceManager;

use Interop\Container\ContainerInterface;

use Zend\Di\DefinitionList;

use Zend\Di\Definition\ArrayDefinition;
use Zend\Di\Definition\DefinitionInterface;
use Zend\Di\Definition\RuntimeDefinition;

use Zend\ServiceManager\Factory\FactoryInterface;


/**
 * Factory implementation for creating the definition list
 */
class DefinitionListFactory implements FactoryInterface
{
    /**
     * Build a definition instance from a container
     *
     * @param ContainerInterface $container
     * @param string|DefinitionInterface $definition
     * @return DefinitionInterface
     */
    private function buildDefinition(ContainerInterface $container, $definition)
    {
        if (is_string($definition) && $container->has($definition)) {
            $definition = $container->get($definition);
        }

        return $definition;
    }

    /**
     * Check the traversability of a variable
     *
     * @param mixed $value
     * @return bool
     */
    protected function canTraverse($value)
    {
        return (is_array($value) || ($value instanceof \Traversable));
    }

    /**
     * Add array definitions to the given defintion list
     *
     * @param DefinitionList $list
     * @param array $config
     */
    protected function addArrayDefintions(DefinitionList $list, $config)
    {
        if (!isset($config['array']) || !$this->canTraverse($config['array'])) {
            return;
        }

        foreach ($config['array'] as $definition) {
            if (is_string($definition)) {
                $path = stream_resolve_include_path($definition);
                if (!$path || !is_readable($path)) {
                    continue;
                }

                $definition = include $path;
            }

            if (!is_array($definition)) {
                continue;
            }

            $list->addDefinition(new ArrayDefinition($definition));
        }
    }

    /**
     * Adds custom definitions to the given definition list
     *
     * @param DefinitionList $list
     * @param ContainerInterface $container
     * @param array $config
     */
    protected function addCustomDefinitions(DefinitionList $list, ContainerInterface $container, $config)
    {
        if (!isset($config['custom']) || !$this->canTraverse($config['custom'])) {
            return;
        }

        foreach ($config['custom'] as $definition) {
            $definition = $this->buildDefinition($container, $definition);

            if ($definition instanceof DefinitionInterface) {
                $list->addDefinition($definition);
            }
        }
    }

    /**
     * Builds the definition list
     *
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $list = new DefinitionList();
        $config = $container->has('config')? $container->get('config') : [];
        $definitionConfig = isset($config['di']['definitions'])? $config['di']['definitions'] : [];

        $this->addArrayDefintions($list, $options);
        $this->addArrayDefintions($list, $definitionConfig);
        $this->addCustomDefinitions($list, $container, $options);
        $this->addCustomDefinitions($list, $container, $definitionConfig);

        $list->addDefinition(new RuntimeDefinition());

        return $list;
    }
}