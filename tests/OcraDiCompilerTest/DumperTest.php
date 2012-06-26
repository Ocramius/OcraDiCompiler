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
                    'a' => __NAMESPACE__ . '\TestAsset\DumperTestDummyInstance',
                    'b' => __NAMESPACE__ . '\TestAsset\DumperTestDummyDependency',
                ),
            ),
            'definition' => array(
                'class' => array(
                    __NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions' => array(
                        'methods' => array(
                            '__construct' => array(
                                'something' => array(
                                    'type' => __NAMESPACE__ . '\TestAsset\DumperTestDummyDependencyFromDefinitions',
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
        $this->assertContains(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions', $initialDefinitions);
    }

    /**
     * Verifies that getAliases proxies correctly to Di instance
     */
    public function testGetAliases()
    {
        $this->assertSame(
            array(
                'a' => __NAMESPACE__ . '\TestAsset\DumperTestDummyInstance',
                'b' => __NAMESPACE__ . '\TestAsset\DumperTestDummyDependency',
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
        $this->assertSame(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependency', $dumpedInstances[$name]->getClass());
    }

    /**
     * Verifies that simple instance classes can be discovered
     */
    public function testGetClassesForDumperTestDummyDependency()
    {
        $name = 'b';
        $classes = $this->getDumper()->getClasses($name);
        $this->assertCount(1, $classes);
        $this->assertSame(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependency', $classes[0]);
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
        $this->assertSame(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstance', $dumpedInstances[$name]->getClass());
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependency', $dumpedInstances);
    }

    /**
     * Verifies that classes can be discovered for instances with dependencies
     */
    public function testGetClassesForDumperTestDummyInstance()
    {
        $name = 'a';
        $classes = $this->getDumper()->getClasses($name);
        $this->assertCount(2, $classes);
        $classes = array_flip($classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstance', $classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependency', $classes);
    }

    /**
     * Verifies that instances with dependencies (defined through Di definitions) can be fetched, and that dependencies
     * will be dumped too
     */
    public function testGetInjectedDefinitionsForDumperDumperTestDummyInstanceWithDefinitions()
    {
        $name = __NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions';
        $dumpedInstances = $this->getDumper()->getInjectedDefinitions($name);
        $this->assertCount(2, $dumpedInstances);
        $this->assertSame($name, $dumpedInstances[$name]->getName());
        $this->assertSame($name, $dumpedInstances[$name]->getClass());
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependencyFromDefinitions', $dumpedInstances);
    }

    /**
     * Verifies that classes can be discovered for instances with dependencies (defined through Di definitions)
     */
    public function testGetClassesForDumperDumperTestDummyInstanceWithDefinitions()
    {
        $name = __NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions';
        $classes = $this->getDumper()->getClasses($name);
        $this->assertCount(2, $classes);
        $classes = array_flip($classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions', $classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependencyFromDefinitions', $classes);
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
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependency', $dumpedInstances);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions', $dumpedInstances);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependencyFromDefinitions', $dumpedInstances);
    }

    /**
     * Verifies that classes can be discovered for instances with dependencies (defined through Di definitions)
     */
    public function testGetAllClasses()
    {
        $classes = $this->getDumper()->getAllClasses();
        $this->assertCount(4, $classes);
        $classes = array_flip($classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstance', $classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependency', $classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyInstanceWithDefinitions', $classes);
        $this->assertArrayHasKey(__NAMESPACE__ . '\TestAsset\DumperTestDummyDependencyFromDefinitions', $classes);
    }
}