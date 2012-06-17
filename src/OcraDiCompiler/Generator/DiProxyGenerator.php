<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace OcraDiCompiler\Generator;

use Zend\Di\Di;
use Zend\Di\ServiceLocator\GeneratorInstance;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Di\Exception;

use OcraDiCompiler\Dumper;
use OcraDiCompiler\Exception\InvalidArgumentException;

/**
 * Class responsible for generating a file containing a Di proxy built from a Zend\Di dump
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class DiProxyGenerator
{
    /**
     * @var Dumper
     */
    protected $dumper;

    /**
     * @var string
     */
    protected $containerClass = 'CompiledDiContainer';

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var ClassGenerator
     */
    protected $classGenerator;

    /**
     * @var FileGenerator
     */
    protected $fileGenerator;

    /**
     * Constructor
     *
     * @param Dumper $dumper
     */
    public function __construct(Dumper $dumper)
    {
        $this->dumper = $dumper;
        $this->classGenerator = new ClassGenerator();
        $this->fileGenerator = new FileGenerator();
    }

    /**
     * Set the class name for the generated service locator container
     *
     * @param  string $name
     * @return DiProxyGenerator
     */
    public function setContainerClass($name)
    {
        $this->containerClass = $name;
        return $this;
    }

    /**
     * Get the class name for the generated service locator container
     *
     * @return string
     */
    public function getContainerClass()
    {
        return $this->containerClass;
    }

    /**
     * Set the namespace to use for the generated class file
     *
     * @param  string $namespace
     * @return DiProxyGenerator
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Get the namespace to use for the generated class file
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Set filename to use for the generated class file
     *
     * @param string $filename
     * @return DiProxyGenerator
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Get filename to use for the generated class file
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Get class generator
     *
     * @return \Zend\Code\Generator\ClassGenerator
     */
    public function getClassGenerator()
    {
        return $this->classGenerator;
    }

    /**
     * Get file generator
     *
     * @return FileGenerator
     */
    public function getFileGenerator()
    {
        return $this->fileGenerator;
    }

    /**
     * Compiles a Di Definitions to a in a service locator, that extends Zend\Di\Di and writes it to disk
     *
     * It uses Zend\Code\Generator\FileGenerator
     *
     * @return FileGenerator
     * @throws InvalidArgumentException
     */
    public function compile()
    {
        $indent         = '    ';
        $caseStatements = array();
        $getters        = array();
        $instances      = $this->dumper->getAllInjectedDefinitions();

        /* @var $instance GeneratorInstance */
        foreach ($instances as $name => $instance) {
            $getter = $this->normalizeAlias($name);
            $constructor = $instance->getConstructor();
            $instantiatorParams = $this->buildParams($instance->getParams());

            if ('__construct' !== $constructor) {
                // Constructor callback
                if (is_callable($constructor)) {
                    $callback = $constructor;

                    if (is_array($callback)) {
                        $class = (is_object($callback[0])) ? get_class($callback[0]) : $callback[0];
                        $method = $callback[1];
                    } elseif (is_string($callback) && strpos($callback, '::') !== false) {
                        list($class, $method) = explode('::', $callback, 2);
                    }

                    $callback = var_export(array($class, $method), true);

                    if (count($instantiatorParams)) {
                        $creation = sprintf('$object = call_user_func(%s, %s);', $callback, implode(', ', $instantiatorParams));
                    } else {
                        $creation = sprintf('$object = call_user_func(%s);', $callback);
                    }
                } else if (is_string($constructor) && strpos($constructor, '->') !== false) {
                    list($class, $method) = explode('->', $constructor, 2);

                    if (!class_exists($class)) {
                        throw new InvalidArgumentException('No class found: ' . $class);
                    }

                    $factoryGetter = $this->normalizeAlias($class);

                    if (count($instantiatorParams)) {
                        $creation = sprintf('$object = $this->' . $factoryGetter . '()->%s(%s);', $method, implode(', ', $instantiatorParams));
                    } else {
                        $creation = sprintf('$object = $this->' . $factoryGetter . '()->%s();', $method);
                    }
                } else {
                    throw new InvalidArgumentException('Invalid instantiator supplied for class: ' . $name);
                }
            } else {
                $className = '\\' . trim($this->reduceAlias($name), '\\');

                if (count($instantiatorParams)) {
                    $creation = sprintf('$object = new %s(%s);', $className, implode(', ', $instantiatorParams));
                } else {
                    $creation = sprintf('$object = new %s();', $className);
                }
            }


            // Create method call code
            if ($instance->isShared()) {
                $creation .= "\n\nif (\$isShared) {\n" . $indent
                    . '$this->instanceManager->addSharedInstance($object, \'' . $instance->getName() . '\');'
                    . "\n}\n";
            }
            $methods = '';

            foreach ($instance->getMethods() as $methodData) {
                $methodName   = $methodData['method'];
                $methodParams = $methodData['params'];
                // Create method parameter representation
                $params = $this->buildParams($methodParams);

                if (count($params)) {
                    $methods .= sprintf("\$object->%s(%s);\n", $methodName, implode(', ', $params));
                }
            }

            $storage = '';

            // Start creating getter
            $getterBody = '';

            // Creation and method calls
            $getterBody .= sprintf("%s\n", $creation);
            $getterBody .= $methods;

            // Stored service
            $getterBody .= $storage;

            // End getter body
            $getterBody .= "return \$object;\n";

            $getterDef = new MethodGenerator();
            $getterDef->setName($getter);
            $getterDef->setParameter('isShared');
            $getterDef->setVisibility(MethodGenerator::VISIBILITY_PROTECTED);
            $getterDef->setBody($getterBody);
            $getters[] = $getterDef;

            // Build case statement and store
            $statement = '';
            $statement .= sprintf("%scase '%s':\n", $indent, $name);
            $statement .= sprintf("%sreturn \$this->%s(%s);\n", str_repeat($indent, 2), $getter, '$isShared');

            $caseStatements[] = $statement;
        }

        // Build switch statement
        $switch = sprintf(
            "if (%s) {\n%sreturn parent::newInstance(%s, %s, %s);\n}\n",
            '$params',
            $indent,
            '$name',
            '$params',
            '$isShared'
        );
        $switch .= sprintf(
            "switch (%s) {\n%s\n", '$name', implode("\n", $caseStatements)
        );
        $switch .= sprintf(
            "%sdefault:\n%sreturn parent::newInstance(%s, %s, %s);\n",
            $indent,
            str_repeat($indent, 2),
            '$name',
            '$params',
            '$isShared'
        );
        $switch .= "}\n\n";

        // Build newInstance() method
        $nameParam   = new ParameterGenerator();
        $nameParam->setName('name');

        $paramsParam = new ParameterGenerator();
        $paramsParam
            ->setName('params')
            ->setType('array')
            ->setDefaultValue(array());

        $isSharedParam = new ParameterGenerator();
        $isSharedParam
            ->setName('isShared')
            ->setDefaultValue(true);

        $get = new MethodGenerator();
        $get->setName('newInstance');
        $get->setParameters(array(
            $nameParam,
            $paramsParam,
            $isSharedParam,
        ));
        $get->setBody($switch);

        // Create class code generation object
        $container = $this->getClassGenerator();
        $container
            ->setName($this->containerClass)
            ->setExtendedClass('Di')
            ->addMethods(array($get))
            ->addMEthods($getters);

        // Create PHP file code generation object
        $classFile = $this->getFileGenerator();

        $classFile->setClass($container);
        $classFile->setUse('Zend\Di\Di');

        if (null !== $this->namespace) {
            $classFile->setNamespace($this->namespace);
        }

        return $classFile;
    }

    /**
     * Reduce aliases
     *
     * @param  string $name
     * @return string
     */
    protected function reduceAlias($name)
    {
        $aliases = $this->dumper->getAliases();

        if (isset($aliases[$name])) {
            return $this->reduceAlias($aliases[$name]);
        }

        return $name;
    }

    /**
     * Normalize an alias to a new instance method name
     *
     * @param  string $alias
     * @return string
     */
    protected function normalizeAlias($alias)
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]/', ' ', $alias);
        $normalized = 'new' . str_replace(' ', '', ucwords($normalized));
        return $normalized;
    }

    /**
     * Generates parameter strings to be used as injections, replacing reference parameters with their respective
     * getters
     *
     * @param array $params
     * @return array
     */
    protected function buildParams(array $params)
    {
        $normalizedParameters = array();

        foreach ($params as $parameter) {
            if ($parameter instanceof GeneratorInstance) {
                /* @var $parameter GeneratorInstance */
                $normalizedParameters[] = sprintf('$this->get(%s)', '\'' . $parameter->getName() . '\'');
            } else {
                $normalizedParameters[] = var_export($parameter, true);
            }
        }

        return $normalizedParameters;
    }
}
