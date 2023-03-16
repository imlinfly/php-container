<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/20 20:44:33
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * Class Container
 * @package LinFly
 * @method static mixed get(string $id)
 * @method static bool has(string $id)
 * @method static bool hasInstance(string $name)
 * @method static bool isSingle(string $name)
 * @method static mixed make(string $name, array $arguments = [], bool $newInstance = false)
 * @method static mixed newInstance(string $name, array $arguments = [])
 * @method static mixed getSingle(string $name, array $arguments = [])
 * @method static mixed invokeMethod(array $name, array $arguments = [])
 * @method static mixed invokeFunction(string|Closure $function, array $arguments = [])
 * @method static mixed invokeClass(string $name, array $arguments = [])
 * @method static string getDefinition(string $name)
 * @method static Container definition(string|array $name, mixed $definition = null)
 * @method static Container bindCallbackBeforeCall(string|Closure $name, callable $callback)
 * @method static Container bindCallbackAfterCall(string|Closure $name, callable $callback)
 * @method static object newClassInstance(ReflectionClass $reflector, array $arguments = [])
 * @method static Closure|null getNewClassInstanceHandler()
 * @method static static setNewClassInstanceHandler(Closure $newClassInstanceHandler)
 * @mixin Container
 * @see Container
 */
final class FacadeContainer
{
    /**
     * Container instance.
     * @var Container
     */
    private static Container $instance;

    /**
     * Get the container instance.
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (!isset(self::$instance)) {
            self::$instance = new Container();
            // Register psr interface to container.
            self::$instance->definition(ContainerInterface::class, self::$instance);
            // Register self to container.
            self::$instance->definition(Container::class, self::$instance);
        }

        return self::$instance;
    }

    /**
     * Call the container instance method.
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return self::getInstance()->$name(...$arguments);
    }
}
