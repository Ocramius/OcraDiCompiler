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

use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\EventManager\Event;
use Zend\Loader\StandardAutoloader;

use Zend\Di\Di;

use OcraDiCompiler\Dumper;
use OcraDiCompiler\Generator\DiProxyGenerator;

/**
 * Module that overrides Di factory with a compiled Di factory. That allows great performance improvements.
 * It lazily checks if a compiled Di file/class was found/defined.. If set, uses it to replace the Di in the
 * ServiceManager.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class Module implements BootstrapListenerInterface, ServiceProviderInterface, AutoloaderProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function onBootstrap(Event $e)
    {
        // @todo replace Di factory and return early

        /* @var $application \Zend\Mvc\ApplicationInterface */
        $application = $e->getTarget();
        $sm = $application->getServiceManager();
        if ($sm->has('Di')) {
            $di = $sm->get('Di');

            if (!$di instanceof Di) {
                // @todo throw?
                return;
            }

            $this->compileDi($di);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceConfiguration()
    {
        return array(
            // @todo custom factory for Di
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Writes Di definitions to a file
     *
     * @param Di $di
     */
    protected function compileDi(Di $di)
    {
        $generator = new DiProxyGenerator(new Dumper($di));
        $fileGenerator = $generator->compile();
        $fileGenerator->setFilename('data/CompiledDi.php');
        $fileGenerator->write();
    }
}