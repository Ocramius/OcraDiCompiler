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

namespace OcraDiCompiler\ServiceManager\Di;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;
use Zend\Di\Exception\ClassNotFoundException;
use Zend\Mvc\Exception\DomainException;
use OcraDiCompiler\Di\CompiledInstantiatorsDi;

/**
 * A Di service factory that allows instantiation of a strict set of service names, but instantiating them and pulling
 * their dependencies from Zend\Di
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 * @see Zend\Mvc\Service\DiStrictAbstractServiceFactory
 */
class DiAbstractServiceFactory extends CompiledInstantiatorsDi implements AbstractFactoryInterface
{
    /**@#+
     * constants
     */
    const USE_SL_BEFORE_DI = 'before';
    const USE_SL_AFTER_DI  = 'after';
    const USE_SL_NONE      = 'none';
    /**@#-*/

    /**
     * @var CompiledInstantiatorsDi
     */
    protected $di;

    /**
     * @var string
     */
    protected $useServiceLocator = self::USE_SL_AFTER_DI;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @param CompiledInstantiatorsDi $di
     * @param string $useServiceLocator
     */
    public function __construct(CompiledInstantiatorsDi $di, $useServiceLocator = self::USE_SL_NONE)
    {
        $this->useServiceLocator = $useServiceLocator;
        // since we are using this in a proxy-fashion, localize state
        $this->di              = $di;
        $this->definitions     = $this->di->definitions;
        $this->instanceManager = $this->di->instanceManager;
    }

    /**
     * {@inheritDoc}
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $serviceName, $requestedName)
    {
        $this->serviceLocator = $serviceLocator;

        return $this->get($requestedName);
    }

    /**
     * Overrides Zend\Di to allow the given serviceLocator's services to be reused by Di itself
     *
     * {@inheritDoc}
     */
    public function get($name, array $params = array())
    {
        if (null === $this->serviceLocator) {
            throw new DomainException('No ServiceLocator defined, use `createServiceWithName` instead of `get`');
        }

        if (self::USE_SL_BEFORE_DI === $this->useServiceLocator && $this->serviceLocator->has($name)) {
            return $this->serviceLocator->get($name);
        }

        try {
            return parent::get($name, $params);
        } catch (ClassNotFoundException $e) {
            if (self::USE_SL_AFTER_DI === $this->useServiceLocator && $this->serviceLocator->has($name)) {
                return $this->serviceLocator->get($name);
            }

            throw new Exception\ServiceNotFoundException(
                sprintf('Service %s was not found in this DI instance', $name),
                null,
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->instanceManager->hasSharedInstance($requestedName)
            || $this->instanceManager->hasAlias($requestedName)
            || $this->instanceManager->hasConfig($requestedName)
            || $this->instanceManager->hasTypePreferences($requestedName)
            || $this->definitions->hasClass($requestedName);
    }
}
