<?php

namespace OcraDiCompiler\Service\Mvc;

use Zend\Mvc\Service\ControllerLoaderFactory as ZendControllerLoaderFactory;
use Zend\ServiceManager\Di\DiServiceInitializer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\EventManager\EventManagerAwareInterface;
use OcraDiCompiler\Service\AbstractWrappedDiServiceFactory;

class ControllerLoaderFactory extends ZendControllerLoaderFactory
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator instanceof ServiceManager) {
            return $serviceLocator;
        }

        /* @var $serviceLocator ServiceManager */
        /* @var $controllerLoader ServiceManager */
        $controllerLoader = $serviceLocator->createScopedServiceManager();

        $configuration    = $serviceLocator->get('Configuration');
        if (isset($configuration['di']) && $serviceLocator->has('Di')) {
            $di = $serviceLocator->get('Di');
            $controllerLoader->addAbstractFactory(
                new AbstractWrappedDiServiceFactory($di, AbstractWrappedDiServiceFactory::USE_SL_BEFORE_DI)
            );
            // @todo is the service initializer really needed? Also, do we compile Di to handle injections this way?
            // @todo this causes recursions that we cannot really handle
            //$controllerLoader->addInitializer(
            //    new DiServiceInitializer($di, $serviceLocator)
            //);
        }

        // @todo these initializer should probably be instantiated in some other factory
        $controllerLoader->addInitializer(function ($instance) use ($serviceLocator) {
            if ($instance instanceof ServiceLocatorAwareInterface) {
                /* @var $instance ServiceLocatorAwareInterface */
                $instance->setServiceLocator($serviceLocator->get('Zend\ServiceManager\ServiceLocatorInterface'));
            }

            if ($instance instanceof EventManagerAwareInterface) {
                /* @var $instance EventManagerAwareInterface */
                $instance->setEventManager($serviceLocator->get('EventManager'));
            }

            if (method_exists($instance, 'setPluginManager')) {
                $instance->setPluginManager($serviceLocator->get('ControllerPluginBroker'));
            }
        });

        return $controllerLoader;
    }
}
