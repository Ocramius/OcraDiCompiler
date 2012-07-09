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

use Zend\ServiceManager\Di\DiServiceFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception;
use Zend\Di\Exception\ClassNotFoundException as DiClassNotFoundException;
use Zend\Di\Configuration;
use Zend\Di\DefinitionList;
use Zend\Di\InstanceManager;

/**
 * Class that wraps the functionality of a ServiceFactory around a Di instance
 */
class WrappedDiServiceFactory extends DiServiceFactory
{
    /**
     * {@inheritDoc}
     */
    public function get($name, array $params = array())
    {
        // allow this di service to get dependencies from the service locator BEFORE trying di
        if (
            self::USE_SL_BEFORE_DI === $this->useServiceLocator
            && $this->serviceLocator
            && $this->serviceLocator->has($name)
        ) {
            return $this->serviceLocator->get($name);
        }

        try {
            return $this->di->get($name, $params);
        } catch (DiClassNotFoundException $e) {
            // allow this di service to get dependencies from the service locator AFTER trying di
            if (
                self::USE_SL_AFTER_DI === $this->useServiceLocator
                && $this->serviceLocator
                && $this->serviceLocator->has($name)
            ) {
                return $this->serviceLocator->get($name);
            } else {
                throw new Exception\ServiceNotFoundException(
                    sprintf('Service %s was not found in this DI instance', $name),
                    null,
                    $e
                );
            }
        }
    }

    // proxying Di public api to wrapped instance

    /**
     * {@inheritDoc}
     */
    public function configure(Configuration $config)
    {
        $this->di->configure($config);
    }

    /**
     * {@inheritDoc}
     */
    public function setDefinitionList(DefinitionList $definitions)
    {
        $this->di->setDefinitionList($definitions);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function definitions()
    {
        return $this->di->definitions();
    }

    /**
     * {@inheritDoc}
     */
    public function setInstanceManager(InstanceManager $instanceManager)
    {
        $this->di->setInstanceManager($instanceManager);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function instanceManager()
    {
        return $this->di->instanceManager();
    }

    /**
     * {@inheritDoc}
     */
    public function newInstance($name, array $params = array(), $isShared = true)
    {
        return $this->di->newInstance($name, $params, $isShared);
    }

    /**
     * {@inheritDoc}
     */
    public function injectDependencies($instance, array $params = array())
    {
        $this->di->injectDependencies($instance, $params);
    }
}
