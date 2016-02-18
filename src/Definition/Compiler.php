<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Di\Definition;

use Zend\Di\Exception\RuntimeException;

use Zend\Code\Scanner\AggregateDirectoryScanner;
use Zend\Code\Scanner\DerivedClassScanner;
use Zend\Code\Scanner\DirectoryScanner;
use Zend\Code\Scanner\FileScanner;

use Zend\Stdlib\ErrorHandler;

/**
 * Class definitions based on a set of directories to be scanned
 */
class Compiler
{
    /**
     * Flag if the defintion is already compiled
     *
     * @var bool
     */
    protected $isCompiled = false;

    /**
     * @var RuntimeDefinition
     */
    protected $definition;

    /**
     * Flag to allow reflection exceptions
     *
     * @var bool
     */
    protected $allowReflectionExceptions = false;

    /**
     * @var AggregateDirectoryScanner
     */
    protected $directoryScanner = null;

    /**
     * Constructor
     *
     * @param IntrospectionStrategy $introspectionStrategy
     */
    public function __construct(IntrospectionStrategy $introspectionStrategy = null)
    {
        $this->definition = new RuntimeDefinition($introspectionStrategy);
        $this->directoryScanner = new AggregateDirectoryScanner();
    }

    /**
     * Set introspection strategy
     *
     * @param IntrospectionStrategy $introspectionStrategy
     */
    public function setIntrospectionStrategy(IntrospectionStrategy $introspectionStrategy)
    {
        $this->definition->setIntrospectionStrategy($introspectionStrategy);
    }

    /**
     * @param bool $allowReflectionExceptions
     */
    public function setAllowReflectionExceptions($allowReflectionExceptions = true)
    {
        $this->allowReflectionExceptions = (bool)$allowReflectionExceptions;
    }

    /**
     * Add directory
     *
     * @param string $directory
     */
    public function addDirectory($directory)
    {
        $this->addDirectoryScanner(new DirectoryScanner($directory));
    }

    /**
     * Add directory scanner
     *
     * @param DirectoryScanner $directoryScanner
     */
    public function addDirectoryScanner(DirectoryScanner $directoryScanner)
    {
        $this->directoryScanner->addDirectoryScanner($directoryScanner);
    }

    /**
     * Add code scanner file
     *
     * @param FileScanner $fileScanner
     */
    public function addCodeScannerFile(FileScanner $fileScanner)
    {
        if ($this->directoryScanner === null) {
            $this->directoryScanner = new DirectoryScanner();
        }

        $this->directoryScanner->addFileScanner($fileScanner);
    }

    /**
     * Compile
     *
     * @return void
     */
    public function compile()
    {
        if ($this->isCompiled) {
            return;
        }

        /* @var $classScanner DerivedClassScanner */
        foreach ($this->directoryScanner->getClassNames() as $class) {
            $this->processClass($class);
        }

        $this->isCompiled = true;

        return $this;
    }

    /**
     * Compile the definition and save it to the given file
     *
     * @param string $file
     * @throws RuntimeException
     */
    public function toFile($file)
    {
        $this->compile();
        ErrorHandler::start();

        if (!file_put_contents($file, '<?php /* Generated DI Defintion */ return ' . var_export($this->toArray(), true) . ';')) {
            throw new RuntimeException(sprintf('Failed to save definion to file "%s"', $file));
        }

        ErrorHandler::stop(true);
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->definition->toArray();
    }

    /**
     * @return DefinitionInterface
     */
    public function getDefinion()
    {
        return $this->definition;
    }

    /**
     * @param  string               $class
     * @throws \ReflectionException
     */
    protected function processClass($class)
    {
        try {
            $this->definition->forceLoadClass($class);
        } catch (\ReflectionException $e) {
            if (!$this->allowReflectionExceptions) {
                throw $e;
            }
        }
    }
}
