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

namespace OcraDiCompiler\Definition;

use Zend\Di\Definition\CompilerDefinition;

/**
 * Class used to compile definitions based for a list of given classes/directories
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ClassListCompilerDefinition extends CompilerDefinition
{
    /**
     * @var array of `$className => true`
     */
    protected $classesToProcess = array();

    /**
     * Adds an array of classes to be processed
     *
     * @param array $classNames
     */
    public function addClassesToProcess(array $classNames)
    {
        foreach ($classNames as $className) {
            $this->addClassToProcess($className);
        }
    }

    /**
     * Adds a class to the list of classes to be processed
     *
     * @param string $className
     * @return bool true if the class was added, false if the class does not exist
     */
    public function addClassToProcess($className)
    {
        if (!class_exists($className)) {
            return false;
        }

        $this->classesToProcess[(string) $className] = true;
        return true;
    }

    /**
     * @{inheritDoc}
     */
    public function compile()
    {
        $toProcess = array_unique(array_merge(
            array_keys($this->classesToProcess),
            $this->directoryScanner->getClassNames()
        ));

        /* @var $classScanner \Zend\Code\Scanner\DerivedClassScanner */
        foreach ($toProcess as $class) {
            $this->processClass($class);
        }
    }
}
