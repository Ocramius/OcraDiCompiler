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

use Zend\Mvc\Controller\ControllerManager;
use OcraDiCompiler\Mvc\Service\DiStrictAbstractServiceFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory responsible for creating the serviceManager responsible for creating controllers
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 * @see Zend\Mvc\Service\ControllerLoaderFactory
 */
class ControllerLoaderFactory implements FactoryInterface
{
    /**
     * Create the controller loader service
     *
     * Creates and returns an instance of Controller\ControllerManager. The
     * only controllers this manager will allow are those defined in the
     * application configuration's "controllers" array. If a controller is
     * matched, the scoped manager will attempt to load the controller.
     * Finally, it will attempt to inject the controller plugin manager
     * if the controller implements a setPluginManager() method.
     *
     * This plugin manager is _not_ peered against DI, and as such, will
     * not load unknown classes.
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ControllerManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $controllerLoader = new ControllerManager();
        $controllerLoader->setServiceLocator($serviceLocator);
        $controllerLoader->addPeeringServiceManager($serviceLocator);

        $config = $serviceLocator->get('Config');

        if (isset($config['di']) && isset($config['di']['allowed_controllers']) && $serviceLocator->has('Di')) {
            $diAbstractFactory = new DiStrictAbstractServiceFactory(
                $serviceLocator->get('Di'),
                DiStrictAbstractServiceFactory::USE_SL_BEFORE_DI
            );
            $diAbstractFactory->setAllowedServiceNames($config['di']['allowed_controllers']);

            $controllerLoader->addAbstractFactory($diAbstractFactory);
        }

        return $controllerLoader;
    }
}
