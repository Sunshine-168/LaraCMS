<?php

namespace libs;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use BadMethodCallException;

/**
 * 动态向类添加方法
 */
trait Macroable
{
    protected $macros = [];

    public function macro(string $name, $macro): void
    {
        $this->macros[$name] = $macro;
    }

    /**
     * @throws ReflectionException
     */
    public function mixin($mixin): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            $method->setAccessible(true);

            $this->macro($method->name, $method->invoke($mixin));
        }
    }

    public function hasMacro(string $name): bool
    {
        return isset($this->macros[$name]);
    }

    public function __call($method, $parameters)
    {
        if (! $this->hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = $this->macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }
}