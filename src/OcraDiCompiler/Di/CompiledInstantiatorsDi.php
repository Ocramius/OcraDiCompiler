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

namespace OcraDiCompiler\Di;

use Zend\Di\Di;

/**
 * A Di instance allowing usage of a list of compiled instantiators
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class CompiledInstantiatorsDi extends Di
{
    /**
     * @var \Closure[]
     */
    protected $compiledInstantiators = array();

    /**
     * @param \Closure[] $compiledInstantiators
     *
     * @todo validate elements
     */
    public function setCompiledInstantiators(array $compiledInstantiators)
    {
        $this->compiledInstantiators = $compiledInstantiators;
    }

    /**
     * @return \Closure[]
     */
    public function getCompiledInstantiators()
    {
        return $this->compiledInstantiators;
    }

    /**
     * {@inheritDoc}
     */
    public function newInstance($name, array $params = array(), $isShared = true)
    {
        if (empty($params) && isset($this->compiledInstantiators[$name])) {
            $cb = $this->compiledInstantiators[$name];

            return $cb($this, $isShared);
        }

        return parent::newInstance($name, $params, $isShared);
    }
}