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

namespace OcraDiCompilerTest;

use PHPUnit_Framework_TestCase as BaseTestCase;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service\ServiceManagerConfig;

/**
 * Base test case to be used when a new service manager instance is required
 */
class ServiceManagerTestCase extends BaseTestCase
{
    private static $config = array();

    public static function setServiceManagerConfig(array $config)
    {
        static::$config = $config;
    }

    public static function getServiceManagerConfig()
    {
        return static::$config;
    }

    public function getServiceManager(array $config = null)
    {
        $config = $config ?: static::getServiceManagerConfig();
        $serviceManager = new ServiceManager(new ServiceManagerConfig($config));
        $serviceManager->setService('ApplicationConfig', $config);
        /* @var $moduleManager \Zend\ModuleManager\ModuleManagerInterface */
        $moduleManager = $serviceManager->get('ModuleManager');
        $moduleManager->loadModules();
        return $serviceManager;
    }
}
