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

namespace OcraDiCompiler;

use Zend\Di\Di;
use Zend\Di\ServiceLocator\DependencyInjectorProxy;
use Zend\Di\ServiceLocator\GeneratorInstance;
use Zend\Di\Exception\RuntimeException;
use Zend\Di\Exception\MissingPropertyException;
use OcraDiCompiler\Exception\InvalidArgumentException;

/**
 * Class for handling dumping of dependency injection parameters
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Marco Pivetta <ocramius@gmail.com>
 */
class Dumper
{
    /**
     * @var Di
     */
    protected $di;

    /**
     * @var DependencyInjectorProxy
     */
    protected $proxy;

    /**
     * @param Di $di
     */
    public function __construct(Di $di)
    {
        $this->di = $di;
        $this->proxy = new DependencyInjectorProxy($di);
    }

    /**
     * Retrieves aliases defined for the wrapped Di instance
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->di->instanceManager()->getAliases();
    }

    /**
     * @return array
     */
    public function getInitialInstanceDefinitions()
    {
        $im = $this->di->instanceManager();
        $classes = $im->getClasses();
        $definedClasses = $this->di->definitions()->getClasses();
        $aliases = array_keys($im->getAliases());
        return array_unique(array_merge($classes, $aliases, $definedClasses));
    }

    /**
     * Retrieves all classes that are involved in generating any instances coming from either
     * aliases or instance parameters or definitions and their dependencies
     *
     * @return string[]
     */
    public function getAllClasses()
    {
        return $this->getClasses($this->getInitialInstanceDefinitions());
    }

    /**
     * Retrieves all GeneratorInstances that represent instantiation of any instances coming from either
     * aliases or instance parameters or definitions and their dependencies
     *
     * @return GeneratorInstance[]
     */
    public function getAllInjectedDefinitions()
    {
        return $this->getInjectedDefinitions($this->getInitialInstanceDefinitions());
    }

    /**
     * Retrieves all classes that are involved in generating an instance for $name and its dependencies
     *
     * @param  array|string $name
     * @return string[]
     */
    public function getClasses($name)
    {
        $classes = array();
        $instanceDefinitions = $this->getInjectedDefinitions($name);

        foreach ($instanceDefinitions as $instanceDefinition) {
            if ($instanceDefinition->getClass()) {
                $classes[$instanceDefinition->getClass()] = true;
                continue;
            }

            $alias = $this->di->instanceManager()->getClassFromAlias($instanceDefinition->getName());
            $classes[$alias] = true;
        }

        return array_keys($classes);
    }


    /**
     * Retrieves all GeneratorInstances that represent instantiation of an instance for $name and its dependencies
     *
     * @param  string|array $name name or names of the instances to get
     * @return GeneratorInstance[] all definitions discovered recursively
     */
    public function getInjectedDefinitions($name)
    {
        $names = (array) $name;
        $visited = array();

        foreach ($names as $name) {
            $this->doGetInjectedDefinitions($name, $visited);
        }

        return $visited;
    }

    /**
     * Recursively looks for discovered dependencies
     *
     * @param string $name of the instances to get
     * @param array  $visited the array where discovered instance definitions will be stored
     */
    protected function doGetInjectedDefinitions($name, array &$visited)
    {
        if (isset($visited[$name])) {
            return;
        }

        try {
            $visited[$name] = $this->proxy->get($name);
        } catch (RuntimeException $e) {
            // usually abstract class or interface that cannot be resolved
            return;
        } catch (MissingPropertyException $e) {
            // usually missing parameters required for a particular instance
            return;
        }

        foreach ($visited[$name]->getParams() as $param) {
            if ($param instanceof GeneratorInstance) {
                /* @var $param GeneratorInstance */
                $this->doGetInjectedDefinitions($param->getName(), $visited);
            }
        }

        foreach ($visited[$name]->getMethods() as $method) {
            if (isset($method['params']) && is_array($method['params'])) {
                foreach ($method['params'] as $param) {
                    /* @var $param GeneratorInstance */
                    if ($param instanceof GeneratorInstance) {
                        /* @var $param GeneratorInstance */
                        $this->doGetInjectedDefinitions($param->getName(), $visited);
                    }
                }
            }
        }
    }
}
