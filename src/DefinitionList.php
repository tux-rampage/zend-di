<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di;

use SplDoublyLinkedList;
use Zend\Di\Definition\RuntimeDefinition;
use Zend\Di\Definition\DefinitionInterface;

/**
 * Class definition based on multiple definitions
 *
 * The class definitions are stored in a FIFO list
 */
class DefinitionList extends SplDoublyLinkedList implements Definition\DefinitionInterface
{
    /**
     * An internal hash of class names to responsible defintions
     *
     * @var DefintionInterface[string]
     */
    protected $classes = [];

    /**
     * Alist of runtime defintions
     *
     * @var SplObjectStorage
     */
    protected $runtimeDefinitions;

    /**
     * Creates a new definition list
     *
     * @param Definition\DefinitionInterface|Definition\DefinitionInterface[] $definitions  The initial definitions
     */
    public function __construct($definitions = [])
    {
        $this->runtimeDefinitions = new SplDoublyLinkedList();

        if (!is_array($definitions)) {
            $definitions = [$definitions];
        }

        foreach ($definitions as $definition) {
            $this->addDefinition($definition, true);
        }
    }

    /**
     * Add a definition
     *
     * @param  Definition\DefinitionInterface $definition
     * @param  bool                           $addToBackOfList
     * @return void
     */
    public function addDefinition(Definition\DefinitionInterface $definition, $addToBackOfList = true)
    {
        if ($addToBackOfList) {
            $this->push($definition);
        } else {
            $this->unshift($definition);
        }
    }

    /**
     * Builds a hash of class names pointing to the given definition
     *
     * @param Definition\DefinitionInterface $definition
     * @return Definition\DefinitionInterface[string]       The resulting class name hash
     */
    protected function getDefinitionClassMap(Definition\DefinitionInterface $definition)
    {
        $definitionClasses = $definition->getClasses();
        if (empty($definitionClasses)) {
            return [];
        }

        return array_combine(array_values($definitionClasses), array_fill(0, count($definitionClasses), $definition));
    }

    /**
     * Adds a defintion to the top of the list
     *
     * @see SplDoublyLinkedList::unshift()
     * @param Definition\DefinitionInterface $definition;
     */
    public function unshift($definition)
    {
        parent::unshift($definition);

        if ($definition instanceof RuntimeDefinition) {
            $this->runtimeDefinitions->unshift($definition);
        }

        $this->classes = array_merge($this->classes, $this->getDefinitionClassMap($definition));
    }

    /**
     * Adds a defintion to the bottom of the list
     *
     * @param Defintion\DefinitionInterface $definition
     */
    public function push($definition)
    {
        $result = parent::push($definition);
        if ($definition instanceof RuntimeDefinition) {
            $this->runtimeDefinitions->push($definition);
        }
        $this->classes = array_merge($this->getDefinitionClassMap($definition), $this->classes);
        return $result;
    }

    /**
     * Returns all defintions for a specific type
     *
     * This will return all defintions that are an instance of the given type
     * which also includes instances of classes inheriting from this type.
     *
     * @param  string                           $type   The defintion class name
     * @return Definition\DefinitionInterface[]         The resulting definitions
     */
    public function getDefinitionsByType($type)
    {
        $definitions = [];
        foreach ($this as $definition) {
            if ($definition instanceof $type) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * Get definition by type
     *
     * Retruns the first definition that is an instance of the given type
     *
     * @param  string                         $type
     * @return Definition\DefinitionInterface
     */
    public function getDefinitionByType($type)
    {
        foreach ($this as $definition) {
            if ($definition instanceof $type) {
                return $definition;
            }
        }

        return false;
    }

    /**
     * Returns the first definition which is responsible for the given class
     *
     * @param  string                   $class
     * @return DefinitionInterface|null          The definition for the given class or null
     */
    public function getDefinitionForClass($class)
    {
        if (array_key_exists($class, $this->classes)) {
            return $this->classes[$class];
        }

        /* @var $definition DefinitionInterface */
        foreach ($this as $definition) {
            if ($definition->hasClass($class)) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Returns all known classes
     *
     * This method returns all lasses known to this definition. THis list may not be complete
     * and the defintion may be able to provide additional class definitions depending on the
     * aggregated definitions
     *
     * @return string[] A list of class names
     */
    public function getClasses()
    {
        return array_keys($this->classes);
    }

    /**
     * {@inheritDoc}
     */
    public function hasClass($class)
    {
        if (array_key_exists($class, $this->classes)) {
            return true;
        }
        /** @var $definition Definition\DefinitionInterface */
        foreach ($this->runtimeDefinitions as $definition) {
            if ($definition->hasClass($class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassSupertypes($class)
    {
        $classDefinition = $this->getDefinitionForClass($class);

        if (!$classDefinition) {
            return [];
        }

        $supertypes = $classDefinition->getClassSupertypes($class);

        if (!$classDefinition instanceof Definition\PartialMarker) {
            return $supertypes;
        }

        /** @var $definition Definition\DefinitionInterface */
        foreach ($this as $definition) {
            if (($definition === $classDefinition) || !$definition->hasClass($class)) {
                continue;
            }

            $supertypes = array_merge($supertypes, $definition->getClassSupertypes($class));

            if (!$definition instanceof Definition\PartialMarker) {
                return $supertypes;
            }
        }

        return $supertypes;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstantiator($class)
    {
        $classDefinition = $this->getDefinitionForClass($class);

        if (!$classDefinition) {
            return null;
        }

        $value = $classDefinition->getInstantiator($class);

        if (($value !== null) || !($classDefinition instanceof Definition\PartialMarker)) {
            return $value;
        }

        /** @var $definition Definition\DefinitionInterface */
        foreach ($this as $definition) {
            if (($definition === $classDefinition) || !$definition->hasClass($class)) {
                continue;
            }

            $value = $definition->getInstantiator($class);

            if (($value !== null) || !($definition instanceof Definition\PartialMarker)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethods($class)
    {
        $classDefinition = $this->getDefinitionForClass($class);

        if (false !== ($methods = $classDefinition->hasMethods($class))) {
            return $methods;
        }
        if (! $classDefinition instanceof Definition\PartialMarker) {
            return false;
        }
        /** @var $definition Definition\DefinitionInterface */
        foreach ($this as $definition) {
            if ($definition === $classDefinition) {
                continue;
            }
            if ($definition->hasClass($class)) {
                if ($definition->hasMethods($class) === false && $definition instanceof Definition\PartialMarker) {
                    continue;
                }

                return $definition->hasMethods($class);
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($class, $method)
    {
        if (!$this->hasMethods($class)) {
            return false;
        }
        $classDefinition = $this->getDefinitionForClass($class);
        if ($classDefinition->hasMethod($class, $method)) {
            return true;
        }
        /** @var $definition Definition\DefinitionInterface */
        foreach ($this->runtimeDefinitions as $definition) {
            if ($definition === $classDefinition) {
                continue;
            }
            if ($definition->hasClass($class) && $definition->hasMethod($class, $method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods($class)
    {
        if (false === ($classDefinition = $this->getDefinitionForClass($class))) {
            return [];
        }
        $methods = $classDefinition->getMethods($class);
        if (! $classDefinition instanceof Definition\PartialMarker) {
            return $methods;
        }
        /** @var $definition Definition\DefinitionInterface */
        foreach ($this as $definition) {
            if ($definition === $classDefinition) {
                continue;
            }
            if ($definition->hasClass($class)) {
                if (!$definition instanceof Definition\PartialMarker) {
                    return array_merge($definition->getMethods($class), $methods);
                }

                $methods = array_merge($definition->getMethods($class), $methods);
            }
        }

        return $methods;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethodParameters($class, $method)
    {
        $methodParameters = $this->getMethodParameters($class, $method);
        return ($methodParameters !== []);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodParameters($class, $method)
    {
        if (false === ($classDefinition = $this->getDefinitionForClass($class))) {
            return [];
        }
        if ($classDefinition->hasMethod($class, $method) && $classDefinition->hasMethodParameters($class, $method)) {
            return $classDefinition->getMethodParameters($class, $method);
        }
        /** @var $definition Definition\DefinitionInterface */
        foreach ($this as $definition) {
            if ($definition === $classDefinition) {
                continue;
            }
            if ($definition->hasClass($class)
                && $definition->hasMethod($class, $method)
                && $definition->hasMethodParameters($class, $method)
            ) {
                return $definition->getMethodParameters($class, $method);
            }
        }

        return [];
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\DefinitionInterface::getResolverMode()
     */
    public function getResolverMode($class)
    {
        if (false === ($classDefinition = $this->getDefinitionForClass($class))) {
            return self::RESOLVE_STRICT;
        }

        $mode = $classDefinition->getResolverMode($class);

        if (!$classDefinition instanceof Definition\PartialMarker) {
            return $mode;
        }

        /** @var $definition Definition\DefinitionInterface */
        foreach ($this as $definition) {
            if ($definition === $classDefinition) {
                continue;
            }

            if ($definition->hasClass($class) && (!$definition instanceof Definition\PartialMarker)) {
                return $definition->getResolverMode($class);
            }
        }

        return $mode;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Di\Definition\DefinitionInterface::getMethodRequirementType()
     */
    public function getMethodRequirementType($class, $method)
    {
        if (false === ($classDefinition = $this->getDefinitionForClass($class))) {
            return self::METHOD_IS_OPTIONAL;
        }


        $requirement = $classDefinition->getMethodRequirementType($class, $method);

        if ($classDefinition instanceof Definition\PartialMarker) {
            foreach ($this as $definition) {
                if ((!$definition instanceof Definition\PartialMarker)
                    && $definition->hasClass($class) && $definition->hasMethod($class, $method)) {
                    return $definition->getMethodRequirementType($class, $method);
                }
            }
        }

        return $requirement;
    }
}
