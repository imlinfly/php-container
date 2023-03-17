<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/10 10:16:42
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly;

use Closure;
use Exception;
use LinFly\Container\Interfaces\InstanceCreateInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use LinFly\Exception\NotFoundException;
use ReflectionMethod;
use Throwable;

class Container implements ContainerInterface
{
    /**
     * Container singleton object
     * @var array
     */
    protected array $singleObjects = [];

    /**
     * Container entry object
     * @var array
     */
    protected array $earlyObjects = [];

    /**
     * Container object definitions.
     * @var array
     */
    protected array $definitions = [];

    /**
     * Instance object callback.
     * @var array
     */
    protected array $callbacks = [];

    /**
     * Get instance create handler.
     * @var Closure|null
     */
    protected ?Closure $newClassInstanceHandler;

    /**
     * Get an object instance from the container.
     * @param string $id Class name or alias name.
     * @return mixed
     */
    public function get(string $id): mixed
    {
        return $this->getSingle($id);
    }

    /**
     * Has the instance been created?
     * @param string $id Instance name
     * @return bool
     */
    public function has(string $id): bool
    {
        $name = $this->getDefinition($id);
        return isset($this->definitions[$id])
            || isset($this->singleObjects[$name])
            || isset($this->earlyObjects[$name]);
    }

    /**
     * Has the instance been created?
     * @param string $name
     * @return bool
     */
    public function hasInstance(string $name): bool
    {
        $name = $this->getDefinition($name);
        return isset($this->singleObjects[$name]) || isset($this->earlyObjects[$name]);
    }

    /**
     * Is the instance a singleton?
     * @param string $name
     * @return bool
     */
    public function isSingle(string $name): bool
    {
        $name = $this->getDefinition($name);
        return isset($this->singleObjects[$name]);
    }

    /**
     * Get an object instance from the container.
     * @param string $name Class name or alias
     * @param array $arguments The parameters of the constructor.
     * @param bool $newInstance Whether to create a new instance.
     * @return mixed
     */
    public function make(string $name, array $arguments = [], bool $newInstance = true): mixed
    {
        if ($newInstance) {
            return $this->newInstance($name, $arguments);
        } else {
            return $this->getSingle($name, $arguments);
        }
    }

    /**
     * Get a new instance object.
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws NotFoundException
     * @throws Throwable
     */
    public function newInstance(string $name, array $arguments = []): mixed
    {
        // Get the defined instance name
        $name = $this->getDefinition($name);

        if ($this->definitions[$name] ?? null instanceof Closure) {
            $instance = $this->invokeFunction($this->definitions[$name], $arguments);
        } else {
            $instance = $this->invokeClass($name, $arguments);
        }

        return $instance;
    }

    /**
     * Get a singleton instance object.
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws NotFoundException
     * @throws Throwable
     */
    public function getSingle(string $name, array $arguments = []): mixed
    {
        // Get the defined instance name
        $name = $this->getDefinition($name);

        $this->earlyObjects[$name]['newInstance'] = false;

        return $this->singleObjects[$name] ??= $this->newInstance($name, $arguments);
    }

    /**
     * Invoke class method
     * @param array $name
     * @param array $arguments
     * @return mixed
     */
    public function invokeMethod(array $name, array $arguments = []): mixed
    {
        [$class, $method] = $name;

        try {
            $reflectionMethod = new ReflectionMethod($class, $method);
        } catch (ReflectionException) {
            $class = is_object($class) ? $class::class : $class;
            throw new NotFoundException('Method ' . $class . '->' . $method . '()' . ' does not exist.');
        }

        if (!$reflectionMethod->isStatic()) {
            $class = is_object($class) ? $class : $this->invokeClass($class);
        }

        // Get the parameters of the method.
        $arguments = $this->getArguments($reflectionMethod, $arguments);

        return $reflectionMethod->invokeArgs(is_object($class) ? $class : null, $arguments);
    }

    /**
     * Invoke a function or closure and inject its dependencies.
     * @param string|Closure $function
     * @param array $arguments
     * @return mixed
     */
    public function invokeFunction(string|Closure $function, array $arguments = []): mixed
    {
        try {
            $reflect = new ReflectionFunction($function);
        } catch (ReflectionException) {
            throw new NotFoundException('Function ' . $function . ' not found');
        }

        $arguments = $this->getArguments($reflect, $arguments);

        return $function(...$arguments);
    }

    /**
     * Invoke a class and inject its dependencies.
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function invokeClass(string $name, array $arguments = []): mixed
    {
        try {
            $reflector = new ReflectionClass($name);
        } catch (ReflectionException) {
            throw new NotFoundException('Class ' . $name . ' does not exist');
        }

        $earlyObjects = $this->earlyObjects[$name] ?? null;
        $isNewInstance = $earlyObjects['newInstance'] ?? true;

        if (isset($earlyObjects['instance'])) {
            if (!$isNewInstance) {
                // If it is not a new instance, return directly
                return $earlyObjects['instance'];
            } else {
                throw new Exception('The object of a circular dependency can only be a single instance: ' . $name);
            }
        }

        $constructor = $reflector->getConstructor();

        // Create an instance without calling the constructor.
        $instance = $this->newClassInstance($reflector, $arguments);
        // Is call constructor.
        $isCall = $constructor && $constructor->isPublic();

        // Put the instance into the container singleton object.
        if (!$isNewInstance) {
            $this->singleObjects[$name] = $instance;
        }

        // Put the instance into the container early object.
        $this->earlyObjects[$name]['instance'] = $instance;

        try {
            // Container instance creation callback.
            if ($instance instanceof InstanceCreateInterface) {
                $instance->instanceCreate($arguments);
            }

            // Callback before calling instance.
            $this->invokeCallback('before', $reflector->name, [$instance, $name, $arguments, $reflector]);

            if ($isCall) {
                // Get constructor parameters.
                $arguments = $this->getArguments($constructor, $arguments);
            }

            if ($isCall) {
                // Call constructor.
                $instance->{$constructor->getName()}(...$arguments);
            }

            // Callback after calling instance.
            $this->invokeCallback('after', $reflector->name, [$instance, $name, $arguments, $reflector]);

        } catch (Throwable $e) {
            // Remove the instance from the container singleton object.
            if (!$isNewInstance) {
                unset($this->singleObjects[$name]);
            }
            throw $e;
        } finally {
            // Delete the instance from the container early object.
            unset($this->earlyObjects[$name]);
        }

        return $instance;
    }

    /**
     * Return dependent parameters
     * @param ReflectionFunctionAbstract $reflector
     * @param array $arguments
     * @return array
     */
    protected function getArguments(ReflectionFunctionAbstract $reflector, array $arguments): array
    {
        $parameters = [];

        // Specification parameter information
        $arguments = \array_values($arguments);

        foreach ($reflector->getParameters() as $parameter) {
            // Parameter type
            $type = $parameter->getType();
            // Checks if it is a built-in type
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                // Determine if the incoming parameter is of that type
                if (current($arguments) instanceof $className) {
                    $parameters[] = current($arguments);
                    next($arguments);
                } else {
                    // Get the instance of the class
                    $parameters[] = $this->getSingle($className);
                }
            } elseif (null !== \key($arguments)) {
                $parameters[] = current($arguments);
                next($arguments);
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Get default value
                $parameters[] = $parameter->getDefaultValue();
            } else {
                throw new NotFoundException('Can not resolve parameter ' . $parameter->getName());
            }
        }

        // Returns the result of parameters replacement
        return $parameters;
    }

    /**
     * Get the name of the defined instance
     * @param string $name Class name or alias
     * @return string
     */
    public function getDefinition(string $name): string
    {
        if (isset($this->definitions[$name])) {
            $definition = $this->definitions[$name];
            if (is_string($definition)) {
                return $this->getDefinition($definition);
            }
        }

        return $name;
    }

    /**
     * Set container object definitions.
     * @param string|array $name Class name or alias name.
     * @param string|Closure|object $definition Class name, closure or instance object
     * @return Container
     */
    public function definition(string|array $name, mixed $definition = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->definition($key, $value);
            }
        } else if ($definition instanceof Closure) {
            $this->definitions[$name] = $definition;
        } else if (is_object($definition)) {
            $this->singleObjects[$name] = $definition;
        } else {
            $originalName = $this->getDefinition($name);
            if ($originalName !== $definition) {
                $this->definitions[$originalName] = $definition;
            }
        }

        return $this;
    }

    /**
     * Container callback
     * @param string $type Callback type
     * @param string $name Class name
     * @param array $parameters The parameters of the callback.
     * @return void
     */
    protected function invokeCallback(string $type, string $name, array $parameters = []): void
    {
        foreach ($this->callbacks[$type]['*'] ?? [] as $callback) {
            $callback(...$parameters);
        }

        foreach ($this->callbacks[$type][$name] ?? [] as $callback) {
            $callback(...$parameters);
        }
    }

    /**
     * Bind invoke callback.
     * @param string $type before|after
     * @param string $name Class name or Closure
     * @param callable $callback Callback function
     * @return static
     */
    protected function bindCallback(string $type, string $name, callable $callback): static
    {
        if ($name === '*') {
            $this->callbacks[$type]['*'][] = $callback;
        } else {
            $name = $this->getDefinition($name);
            $this->callbacks[$type][$name][] = $callback;
        }
        return $this;
    }

    /**
     * Bind before invoke callback.
     * @param string $name
     * @param callable $callback
     * @return static
     */
    public function bindCallbackBeforeCall(string $name, callable $callback): static
    {
        return $this->bindCallback('before', $name, $callback);
    }

    /**
     * Bind after invoke callback.
     * @param string $name
     * @param callable $callback
     * @return static
     */
    public function bindCallbackAfterCall(string $name, callable $callback): static
    {
        return $this->bindCallback('after', $name, $callback);
    }

    /**
     * Class new instance
     * @param ReflectionClass $reflector
     * @param array $arguments
     * @return object
     * @throws ReflectionException
     */
    protected function newClassInstance(ReflectionClass $reflector, array $arguments = []): object
    {
        if (isset($this->newClassInstanceHandler)) {
            return ($this->newClassInstanceHandler)($reflector, $arguments);
        }
        return $reflector->newInstanceWithoutConstructor();
    }

    /**
     * @return array|null
     */
    public function getNewClassInstanceHandler(): ?Closure
    {
        return $this->newClassInstanceHandler;
    }

    /**
     * @param Closure $newClassInstanceHandler
     * @return static
     */
    public function setNewClassInstanceHandler(Closure $newClassInstanceHandler): static
    {
        $this->newClassInstanceHandler = $newClassInstanceHandler;
        return $this;
    }
}
