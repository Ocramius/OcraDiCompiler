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
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Di\Exception;

use OcraDiCompiler\Dumper;
use OcraDiCompiler\Exception\InvalidArgumentException;

/**
 * Class responsible for generating a file containing an array of instantiator closures indexed by instance name
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class DiInstantiatorCompiler
{
    /**
     * @var Dumper
     */
    protected $dumper;

    /**
     * @var string
     */
    protected $filename;

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
        $this->fileGenerator = new FileGenerator();
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
     * Set file generator
     *
     * @param FileGenerator
     */
    public function setFileGenerator(FileGenerator $fileGenerator)
    {
        $this->fileGenerator = $fileGenerator;
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
        $indent    = '    ';
        $getters   = array();
        $instances = $this->dumper->getAllInjectedDefinitions();

        /* @var $instance GeneratorInstance */
        foreach ($instances as $name => $instance) {
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

                    if (count($instantiatorParams)) {
                        $creation = sprintf(
                            '$object = $di->get(%s)->%s(%s);',
                            var_export($class, true),
                            $method,
                            implode(', ', $instantiatorParams)
                        );
                    } else {
                        $creation = sprintf('$object = $di->get(%s)->%s();', var_export($class, true), $method);
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
                    . '$di->instanceManager()->addSharedInstance($object, \'' . $instance->getName() . '\');'
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

            // Start creating getter
            $getterBody = "function (\\Zend\\Di\\Di \$di, \$isShared) {\n";

            // Creation and method calls
            $getterBody .= $indent . str_replace("\n", "\n" . $indent, $creation) . "\n";
            $getterBody .= $indent . str_replace("\n", "\n" . $indent, $methods);

            // End getter body
            $getterBody .= "\n" . $indent . "return \$object;\n}";

            $getters[$name] = $getterBody;
        }

        $fileBody = "return array(\n";

        foreach ($getters as $name => $getterBody) {
            $fileBody .= $indent . var_export($name, true);
            $fileBody .= ' => ' . str_replace("\n", "\n" . $indent, $getterBody) . ",\n\n";
        }

        $fileBody .= ");\n";

        // Create PHP file code generation object
        $generator = $this->getFileGenerator();
        $generator->setBody($fileBody);

        return $generator;
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
                $normalizedParameters[] = sprintf('$di->get(%s)', '\'' . $parameter->getName() . '\'');
            } else {
                $normalizedParameters[] = var_export($parameter, true);
            }
        }

        return $normalizedParameters;
    }
}
