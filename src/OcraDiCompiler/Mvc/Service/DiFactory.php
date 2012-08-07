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

namespace OcraDiCompiler\Mvc\Service;

use Zend\Di\Config as DiConfig;
use Zend\Di\Di;
use Zend\Di\Definition\ArrayDefinition;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\FactoryInterface;

use OcraDiCompiler\Dumper;
use OcraDiCompiler\Di\CompiledInstantiatorsDi;
use OcraDiCompiler\ServiceManager\Di\DiAbstractServiceFactory;
use OcraDiCompiler\Definition\ClassListCompilerDefinition;
use OcraDiCompiler\Generator\DiInstantiatorCompiler;

use Zend\Code\Generator\FileGenerator;

/**
 * Factory that is responsible for generating a compiled Di if none found and otherwise
 * instantiate a more performing Di proxy factory
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @todo this file is a mess and must be split into definitions compiler/instance compiler and di factory
 */
class DiFactory implements FactoryInterface
{
    /**
     * Generates a compiled Di proxy to be used as a replacement for \Zend\Di\Di
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Di
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        $compiledInstantiators = @include $config['ocra_di_compiler']['compiled_di_instantiator_filename'];

        if (!is_array($compiledInstantiators)) {
            $di = $this->createDi($config);
            $this->compileDi($di, $config);
            $this->getDiDefinitions($config, $di);
            $compiledInstantiators = include $config['ocra_di_compiler']['compiled_di_instantiator_filename'];
        }

        $di = new CompiledInstantiatorsDi();

        $this->configureDi($di, $config);
        $di->setCompiledInstantiators($compiledInstantiators);
        $this->registerAbstractFactory($serviceLocator, $di);

        return $di;
    }

    /**
     * @param array $config
     * @return Di
     */
    protected function createDi($config)
    {
        $di = new Di();

        if (isset($config['di'])) {
            $di->configure(new DiConfig($config['di']));
        }

        return $di;
    }

    /**
     * @param Di $di
     * @param $config
     */
    protected function configureDi(Di $di, $config) {
        if (isset($config['di'])) {
            if (!isset($config['di']['compiler'])) {
                $config['di']['compiler'] = array();
            }

            $config['di']['compiler'][] = $this->getDiDefinitions($config);
            $diConfig = new DiConfig($config['di']);
            $diConfig->configure($di);
        }
    }

    /**
     * @param ServiceLocatorInterface $sl
     * @param Di $di
     * @return void
     */
    protected function registerAbstractFactory(ServiceLocatorInterface $sl, Di $di) {
        if ($sl instanceof ServiceManager) {
            /* @var $sl ServiceManager */
            $sl->addAbstractFactory(new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI));
        }
    }

    /**
     * @param $config
     * @param null|Di $di
     * @return string
     */
    protected function getDiDefinitions($config, Di $di = null) {
        if ($arrayDefinitions = @include $config['ocra_di_compiler']['compiled_di_definitions_filename']) {
            return $config['ocra_di_compiler']['compiled_di_definitions_filename'];
        }

        if (!$di) {
            $di = new Di();

            if (isset($config['di'])) {
                $diConfig = new DiConfig($config['di']);
                $diConfig->configure($di);
            }
        }

        $dumper = new Dumper($di);
        $definitionsCompiler = new ClassListCompilerDefinition();
        $definitionsCompiler->addClassesToProcess($dumper->getAllClasses());
        $definitionsCompiler->compile();
        $fileGenerator = new FileGenerator();
        $fileGenerator->setFilename($config['ocra_di_compiler']['compiled_di_definitions_filename']);
        $fileGenerator->setBody(
            'return ' . var_export($definitionsCompiler->toArrayDefinition()->toArray(), true) . ';'
        );
        $fileGenerator->write();

        return $config['ocra_di_compiler']['compiled_di_definitions_filename'];
    }

    /**
     * @param Di $di
     * @param $config
     */
    protected function compileDi(Di $di, $config) {
        $generator = new DiInstantiatorCompiler(new Dumper($di));
        $fileGenerator = $generator->compile();
        $fileGenerator->setFilename($config['ocra_di_compiler']['compiled_di_instantiator_filename']);
        $fileGenerator->write();
    }
}