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

namespace OcraDiCompiler\Service;

use Zend\Di\Configuration as DiConfiguration;
use Zend\Di\Di;
use Zend\ServiceManager\Di\DiAbstractServiceFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service\DiFactory;

use OcraDiCompiler\Dumper;
use OcraDiCompiler\Generator\DiProxyGenerator;
use OcraDiCompiler\Exception\InvalidArgumentException;

/**
 * Factory that is responsible for generating a compiled Di if none found and otherwise
 * instantiate a more performing Di proxy factory
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class CompiledDiFactory extends DiFactory
{
    /**
     * Generates a compiled Di proxy to be used as a replacement for \Zend\Di\Di
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Di
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');

        // must use file_exists because of possible exceptions have to be shown
        if (!file_exists($config['ocra_di_compiler']['compiled_di_filename'])) {
            $di = parent::createService($serviceLocator);
            $this->compileDi($di, $config);
            return $di;
        }

        include_once $config['ocra_di_compiler']['compiled_di_filename'];
        $className =  $config['ocra_di_compiler']['compiled_di_namespace'] . '\\'
            . $config['ocra_di_compiler']['compiled_di_classname'];
        /* @var $di Di */
        $di = new $className();

        $this->configureDi($di, $config);
        $this->registerAbstractFactory($serviceLocator, $di);

        return $di;
    }

    protected function configureDi(Di $di, $config) {
        if (isset($config['di'])) {
            $di->configure(new DiConfiguration($config['di']));
        }
    }

    protected function registerAbstractFactory(ServiceLocatorInterface $sl, Di $di) {
        if (!$sl instanceof ServiceManager) {
            return;
        }

        /* @var $sl ServiceManager */
        $sl->addAbstractFactory(new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI));
    }

    protected function compileDi(Di $di, $config) {
        $generator = new DiProxyGenerator(new Dumper($di));
        $fileGenerator = $generator->compile();
        $generator->getClassGenerator()->setName($config['ocra_di_compiler']['compiled_di_classname']);
        $fileGenerator->setFilename($config['ocra_di_compiler']['compiled_di_filename']);
        $fileGenerator->setNamespace($config['ocra_di_compiler']['compiled_di_namespace']);
        $fileGenerator->write();
    }
}