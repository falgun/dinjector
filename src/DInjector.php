<?php
namespace Falgun\DInjector;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Falgun\DInjector\Exceptions\MethodNotCallableException;
use Falgun\DInjector\Exceptions\MethodNotFoundException;

class DInjector
{

    public function __construct()
    {
        
    }

    /**
     * 
     * @param string $class
     * @param string $method
     * @param array $arguments
     * @return type
     * @throws Exception
     */
    public function resolve(string $class, string $method = null, array $arguments = [])
    {
        $reflector = new ReflectionClass($class);

        //is it instatiable ?
        if (!$reflector->isInstantiable()) {
            throw new Exception($class . ' Cant be instatiate !');
        }

        //check constructor
        $constructor = $reflector->getConstructor();

        if ($constructor !== null) {
            //Get constructor parameter and its dependencies
            $dependencies = $this->getDependencies($constructor->getParameters());
        } else {
            $dependencies = [];
        }
        //instantiate class
        $classInistance = $reflector->newInstanceArgs($dependencies);

        // Serve Class Object if it requires
        if ($method === null) {
            return $classInistance;
        }

        if ($reflector->hasMethod($method) === false) {
            /**
             * Invalid Method call
             */
            throw new MethodNotFoundException($method . '() not found in ' . $class);
        }

        /* @var $methodReflector \ReflectionMethod */
        $methodReflector = $reflector->getMethod($method);

        if ($methodReflector->isPublic() === false) {
            /**
             * Function is not public
             * So its not callable at all
             */
            throw new MethodNotCallableException($method . ' is not callable from ' . $class);
        }
        $dependencies = $this->getDependencies($methodReflector->getParameters());

        return $methodReflector->invokeArgs($classInistance, $dependencies);
    }

    /**
     * Get dependency list from parameters
     * @param type $parameters
     * @return type Array
     */
    protected function getDependencies(array $parameters)
    {
        $dependencies = [];

        if (!empty($parameters)) {
            foreach ($parameters as $parameter) {
                /* @var $parameter \ReflectionParameter */
                if ($parameter->getName() === 'resolver') {
                    $dependencies[] = $this;
                    continue;
                }

                $dependency = $parameter->getClass();

                if ($dependency === null) {
                    $dependencies[] = $this->resolveNonClass($parameter);
                } else {
                    $dependencies[] = $this->resolve($dependency->getName(), null, [], true);
                }
            }
        }

        return $dependencies;
    }

    /**
     * Resolve normal parameter
     * @param \ReflectionParameter $parameter
     * @return type
     * @throws \Exception
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception('No default value for ' . $parameter->getName() . ' found !');
    }
}
