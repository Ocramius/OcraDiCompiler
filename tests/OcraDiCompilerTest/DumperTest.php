<?php

namespace OcraDiCompilerTest;

use PHPUnit_Framework_TestCase as BaseTest;
use Zend\Di\Di;
use Zend\Di\Configuration;
use OcraDiCompiler\Dumper;

final class DumperTest extends BaseTest
{
    /**
     * @var array
     */
    protected $diConfigurationArray;

    /**
     * @var Dumper
     */
    protected $dumper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->diConfigurationArray = array(
            'instance' => array(
                'alias' => array(
                    'a' => __NAMESPACE__ . '\DumperTestDummyInstance',
                    'b' => __NAMESPACE__ . '\DumperTestDummyDependency',
                ),
            ),
            'definition' => array(
                'class' => array(
                    __NAMESPACE__ . '\DumperTestDummyInstanceWithDefinitions' => array(
                        'methods' => array(
                            '__construct' => array(
                                'something' => array(
                                    'type' => __NAMESPACE__ . '\DumperTestDummyDependencyFromDefinitions',
                                    'required' => true,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Retrieves a fresh dumper at every call
     *
     * @return Dumper
     */
    protected function getDumper()
    {
        $di = new Di();
        $di->configure(new Configuration($this->diConfigurationArray));
        return new Dumper($di);
    }

    /**
     * Verifies that aliases and definitions are merged correctly
     */
    public function testGetInitialInstanceDefinitions()
    {
        $initialDefinitions = $this->getDumper()->getInitialInstanceDefinitions();
        $this->assertCount(3, $initialDefinitions);
        $this->assertContains('a', $initialDefinitions);
        $this->assertContains('b', $initialDefinitions);
        $this->assertContains(__NAMESPACE__ . '\DumperTestDummyInstanceWithDefinitions', $initialDefinitions);
    }

    /**
     * Verifies that getAliases proxies correctly to Di instance
     */
    public function testGetAliases()
    {
        $this->assertSame(
            array(
                'a' => __NAMESPACE__ . '\DumperTestDummyInstance',
                'b' => __NAMESPACE__ . '\DumperTestDummyDependency',
            ),
            $this->getDumper()->getAliases()
        );
    }

    /**
     * Verifies that simple instances can be dumped
     */
    public function testGetInjectedDefinitionsForDumperTestDummyDependency()
    {
        $name = 'b';
        $dumpedInstances = $this->getDumper()->getInjectedDefinitions($name);
        $this->assertCount(1, $dumpedInstances);
        $this->assertSame($name, $dumpedInstances[$name]->getName());
        $this->assertSame(__NAMESPACE__ . '\DumperTestDummyDependency', $dumpedInstances[$name]->getClass());
    }

    /**
     * Verifies that instances with dependencies can be fetched, and that dependencies will be dumped too
     */
    public function testGetInjectedDefinitionsForDumperTestDummyInstance()
    {
        $name = 'a';
        $dumpedInstances = $this->getDumper()->getInjectedDefinitions($name);
        $this->assertCount(2, $dumpedInstances);
        $this->assertSame($name, $dumpedInstances[$name]->getName());
        $this->assertSame(__NAMESPACE__ . '\DumperTestDummyInstance', $dumpedInstances[$name]->getClass());
        $this->assertArrayHasKey(__NAMESPACE__ . '\DumperTestDummyDependency', $dumpedInstances);
    }

    /**
     * Verifies that instances with dependencies (defined through Di definitions) can be fetched, and that dependencies
     * will be dumped too
     */
    public function testGetInjectedDefinitionsForDumperDumperTestDummyInstanceWithDefinitions()
    {
        $name = __NAMESPACE__ . '\DumperTestDummyInstanceWithDefinitions';
        $dumpedInstances = $this->getDumper()->getInjectedDefinitions($name);
        $this->assertCount(2, $dumpedInstances);
        $this->assertSame($name, $dumpedInstances[$name]->getName());
        $this->assertSame($name, $dumpedInstances[$name]->getClass());
        $this->assertArrayHasKey(__NAMESPACE__ . '\DumperTestDummyDependencyFromDefinitions', $dumpedInstances);
    }

    /**
     * Verifies that all defined instances and definitions will be fetched with all their dependencies
     */
    public function testGetAllInjectedDefinitions()
    {
        $dumpedInstances = $this->getDumper()->getAllInjectedDefinitions();
        $this->assertCount(5, $dumpedInstances);
        $this->assertArrayHasKey('a', $dumpedInstances);
        $this->assertArrayHasKey('b', $dumpedInstances);
        $this->assertArrayHasKey(__NAMESPACE__ . '\DumperTestDummyDependency', $dumpedInstances);
        $this->assertArrayHasKey(__NAMESPACE__ . '\DumperTestDummyInstanceWithDefinitions', $dumpedInstances);
        $this->assertArrayHasKey(__NAMESPACE__ . '\DumperTestDummyDependencyFromDefinitions', $dumpedInstances);
    }
}

/**
 * Instance to be retrieved (aliases should point at it)
 */
class DumperTestDummyInstance
{
    public function __construct(DumperTestDummyDependency $dependency)
    {
    }
}

/**
 * Instance discovered by Di as a dependency of DumperTestDummyInstance
 */
class DumperTestDummyDependency
{

}

/**
 * Instance to be retrieved that has attached definitions
 */
class DumperTestDummyInstanceWithDefinitions
{
    public function __construct($something)
    {
    }
}

/**
 * Instance to be injected into DumperTestDummyInstanceWithDefinitions through its definitions
 */
class DumperTestDummyDependencyFromDefinitions
{
}