<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Unified callback execution helper.
 *
 * Provides a centralized way to execute callbacks with support for:
 * - Registered callbacks (via register())
 * - Static method calls (Class::method or [Class::class, 'method'])
 * - Instance methods (public/private/protected via reflection)
 * - Global functions
 * - Closures
 *
 * Used by:
 * - SimpleDto Attributes (Visible, WhenCallback, etc.)
 * - DataMapper Filters (CallbackFilter, callback: filter)
 * - Template Expressions ({{ value | callback:name }})
 *
 * Example:
 * ```php
 * // Register a callback
 * CallbackHelper::register('slugify', fn($params) => strtolower(str_replace(' ', '-', $params->value)));
 *
 * // Execute registered callback
 * $result = CallbackHelper::execute('slugify', $value, $context);
 *
 * // Execute static method
 * $result = CallbackHelper::execute([PermissionChecker::class, 'canView'], $value, $context);
 *
 * // Execute instance method (with reflection for private methods)
 * $result = CallbackHelper::execute('canViewEmail', $value, $context, $dtoInstance);
 *
 * // Execute global function
 * $result = CallbackHelper::execute('strtoupper', $value, $context);
 * ```
 */
final class CallbackHelper
{
    /**
     * @var array<string, callable>
     * @phpstan-ignore-next-line - Callable signature varies by usage
     */
    private static array $callbacks = [];

    /**
     * Register a named callback.
     *
     * @param string $name Callback name
     * @param callable $callback Callback function
     * @throws InvalidArgumentException If callback name is already registered
     * @phpstan-ignore-next-line - Callable signature varies by usage
     */
    public static function register(string $name, callable $callback): void
    {
        if (isset(self::$callbacks[$name])) {
            throw new InvalidArgumentException(sprintf('Callback "%s" is already registered', $name));
        }

        self::$callbacks[$name] = $callback;
    }

    /**
     * Register a callback, overwriting if it already exists.
     *
     * @param string $name Callback name
     * @param callable $callback Callback function
     * @phpstan-ignore-next-line - Callable signature varies by usage
     */
    public static function registerOrReplace(string $name, callable $callback): void
    {
        self::$callbacks[$name] = $callback;
    }

    /**
     * Get a registered callback by name.
     *
     * @param string $name Callback name
     * @return callable|null The callback or null if not found
     * @phpstan-ignore-next-line - Callable signature varies by usage
     */
    public static function get(string $name): ?callable
    {
        return self::$callbacks[$name] ?? null;
    }

    /**
     * Check if a callback is registered.
     *
     * @param string $name Callback name
     */
    public static function has(string $name): bool
    {
        return isset(self::$callbacks[$name]);
    }

    /**
     * Unregister a callback.
     *
     * @param string $name Callback name
     */
    public static function unregister(string $name): void
    {
        unset(self::$callbacks[$name]);
    }

    /**
     * Clear all registered callbacks.
     */
    public static function clear(): void
    {
        self::$callbacks = [];
    }

    /**
     * Get all registered callback names.
     *
     * @return array<int, string>
     */
    public static function getRegisteredNames(): array
    {
        return array_keys(self::$callbacks);
    }

    /**
     * Get the number of registered callbacks.
     */
    public static function count(): int
    {
        return count(self::$callbacks);
    }

    /**
     * Execute a callback with unified logic.
     *
     * Execution order:
     * 1. Resolve 'static::method' to actual class (if $context is provided)
     * 2. Check if callback is registered (via register())
     * 3. Check if callback is a static method call (Class::method or [Class::class, 'method'])
     * 4. Check if callback is an instance method (requires $instance parameter)
     * 5. Check if callback is a global function
     * 6. Check if callback is a Closure
     * 7. Check if callback is an invokable object
     *
     * @param string|array<string>|Closure|object $callback Callback to execute
     * @param mixed ...$args Arguments to pass to the callback
     * @return mixed Result of the callback
     * @throws InvalidArgumentException If callback cannot be resolved
     */
    public static function execute(mixed $callback, mixed ...$args): mixed
    {
        // 1. Resolve 'static::method' to actual class if first arg is an object
        if (is_string($callback) && str_starts_with($callback, 'static::')) {
            $instance = null;
            foreach ($args as $arg) {
                if (is_object($arg)) {
                    $instance = $arg;
                    break;
                }
            }

            if (null !== $instance) {
                $method = substr($callback, 8); // Remove 'static::'
                $callback = $instance::class . '::' . $method;
            }
        }
        // 1. Check if callback is registered
        if (is_string($callback) && self::has($callback)) {
            $registeredCallback = self::get($callback);
            if (null !== $registeredCallback) {
                return $registeredCallback(...$args);
            }
        }

        // 2. Check if callback is a static method call
        if (is_string($callback) && str_contains($callback, '::')) {
            [$class, $method] = explode('::', $callback, 2);

            if (class_exists($class) && method_exists($class, $method)) {
                try {
                    $reflection = new ReflectionMethod($class, $method);

                    if ($reflection->isStatic()) {
                        return $reflection->invoke(null, ...$args);
                    }
                } catch (ReflectionException) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot execute static method "%s::%s"',
                        $class,
                        $method
                    ));
                }
            }

            throw new InvalidArgumentException(sprintf(
                'Static method "%s::%s" does not exist',
                $class,
                $method
            ));
        }

        // 2b. Check if callback is array format [Class::class, 'method'] or [$instance, 'method']
        if (is_array($callback) && count($callback) === 2) {
            [$classOrInstance, $method] = $callback;

            // Check if it's an instance method: [$instance, 'method']
            if (is_object($classOrInstance) && is_string($method)) {
                if (method_exists($classOrInstance, $method)) {
                    try {
                        $reflection = new ReflectionMethod($classOrInstance, $method);
                        return $reflection->invoke($classOrInstance, ...$args);
                    } catch (ReflectionException) {
                        throw new InvalidArgumentException(sprintf(
                            'Cannot execute instance method "%s::%s"',
                            get_class($classOrInstance),
                            $method
                        ));
                    }
                }

                throw new InvalidArgumentException(sprintf(
                    'Instance method "%s::%s" does not exist',
                    get_class($classOrInstance),
                    $method
                ));
            }

            // Check if it's a static method: [Class::class, 'method']
            if (is_string($classOrInstance) && is_string($method) && class_exists($classOrInstance) && method_exists($classOrInstance, $method)) {
                try {
                    $reflection = new ReflectionMethod($classOrInstance, $method);

                    if ($reflection->isStatic()) {
                        return $reflection->invoke(null, ...$args);
                    }
                } catch (ReflectionException) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot execute static method "%s::%s"',
                        $classOrInstance,
                        $method
                    ));
                }
            }

            throw new InvalidArgumentException(sprintf(
                'Static method "%s::%s" does not exist or is not static',
                is_string($classOrInstance) ? $classOrInstance : gettype($classOrInstance),
                is_string($method) ? $method : gettype($method)
            ));
        }

        // 3. Check if callback is an instance method (requires instance in args)
        if (is_string($callback) && !function_exists($callback)) {
            // Try to find instance in args
            $instance = null;
            $instanceIndex = null;
            foreach ($args as $index => $arg) {
                if (is_object($arg) && method_exists($arg, $callback)) {
                    $instance = $arg;
                    $instanceIndex = $index;
                    break;
                }
            }

            if (null !== $instance) {
                try {
                    $reflection = new ReflectionMethod($instance, $callback);
                    // Remove instance from args only if it's the first argument
                    if (0 === $instanceIndex) {
                        $filteredArgs = array_slice($args, 1);
                        return $reflection->invoke($instance, ...$filteredArgs);
                    }
                    // Otherwise, keep all args except the instance
                    $filteredArgs = array_filter($args, fn($arg) => $arg !== $instance);
                    return $reflection->invoke($instance, ...array_values($filteredArgs));
                } catch (ReflectionException) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot execute instance method "%s" on %s',
                        $callback,
                        get_class($instance)
                    ));
                }
            }
        }

        // 4. Check if callback is a global function
        if (is_string($callback) && function_exists($callback)) {
            try {
                $reflection = new ReflectionFunction($callback);
                return $reflection->invoke(...$args);
            } catch (ReflectionException) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot execute global function "%s"',
                    $callback
                ));
            }
        }

        // 5. Check if callback is a Closure
        if ($callback instanceof Closure) {
            return $callback(...$args);
        }

        // 6. Check if callback is an object (might be invokable or just invalid)
        if (is_object($callback)) {
            // Check if object has __invoke method
            if (method_exists($callback, '__invoke')) {
                try {
                    return $callback(...$args);
                } catch (\TypeError|\Error $e) {
                    throw new InvalidArgumentException(sprintf(
                        'Object of class %s is not callable: %s',
                        get_class($callback),
                        $e->getMessage()
                    ), 0, $e);
                }
            }

            throw new InvalidArgumentException(sprintf(
                'Object of class %s is not callable',
                get_class($callback)
            ));
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot resolve callback: %s',
            is_string($callback) ? $callback : gettype($callback)
        ));
    }
}

