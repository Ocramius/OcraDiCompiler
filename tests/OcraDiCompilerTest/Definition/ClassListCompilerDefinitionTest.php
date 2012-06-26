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

namespace OcraDiCompilerTest\Definition;

use PHPUnit_Framework_TestCase as BaseTest;
use OcraDiCompiler\Definition\ClassListCompilerDefinition;

final class ClassListCompilerDefinitionTest extends BaseTest
{
    /**
     * @var ClassListCompilerDefinition
     */
    protected $compilerDefinition;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->compilerDefinition = new ClassListCompilerDefinition();
    }

    public function willIgnoreNonExistingClasses()
    {
        $this->assertFalse(
            $this->compilerDefinition->addClassToProcess('this_class_does_not_exist')
        );
        $this->compilerDefinition->compile();
        $this->assertCount(0, $this->compilerDefinition->toArrayDefinition()->toArray());
    }

    public function testCanCompileByClassName()
    {
        $this->assertTrue(
            $this->compilerDefinition->addClassToProcess('OcraDiCompilerTest\TestAsset\ExampleEmptyClass')
        );
        $this->compilerDefinition->compile();
        $definition = $this->compilerDefinition->toArrayDefinition()->toArray();
        $this->assertCount(1, $definition);
        $this->assertArrayHasKey('OcraDiCompilerTest\TestAsset\ExampleEmptyClass', $definition);
    }

    public function testCanCompileByClassNames()
    {
        $this->compilerDefinition->addClassesToProcess(array('OcraDiCompilerTest\TestAsset\ExampleEmptyClass'));
        $this->compilerDefinition->compile();
        $definition = $this->compilerDefinition->toArrayDefinition()->toArray();
        $this->assertCount(1, $definition);
        $this->assertArrayHasKey('OcraDiCompilerTest\TestAsset\ExampleEmptyClass', $definition);
    }

    public function testCanCompileByDirectoryName()
    {
        $this->compilerDefinition->addDirectory(__DIR__);
        $this->compilerDefinition->compile();
        $definition = $this->compilerDefinition->toArrayDefinition()->toArray();
        $this->assertCount(1, $definition);
        $this->assertArrayHasKey(__CLASS__, $definition);
    }

    public function testCanCompileByDirectoryAndClassName()
    {
        $this->compilerDefinition->addClassToProcess('OcraDiCompilerTest\TestAsset\ExampleEmptyClass');
        $this->compilerDefinition->addDirectory(__DIR__);
        $this->compilerDefinition->compile();
        $definition = $this->compilerDefinition->toArrayDefinition()->toArray();
        $this->assertCount(2, $definition);
        $this->assertArrayHasKey('OcraDiCompilerTest\TestAsset\ExampleEmptyClass', $definition);
        $this->assertArrayHasKey(__CLASS__, $definition);
    }

    public function testWillCompileParentClasses()
    {
        $this->markTestIncomplete('Feature not yet implemented: parent classes are not discovered automatically');
    }
}
