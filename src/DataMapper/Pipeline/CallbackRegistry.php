<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use Closure;
use InvalidArgumentException;

/**
 * Registry for named callback transformers.
 *
 * Allows registering callbacks with names that can be used in template expressions.
 *
 * Example:
 *   // Register a callback
 *   CallbackRegistry::register('upper', fn($p) => strtoupper($p->value));
 *
 *   // Use in template
 *   $template = ['name' => '{{ user.name | callback:upper }}'];
 */
final class CallbackRegistry
{
    /** @var array<string, Closure(CallbackParameters): mixed> */
    private static array $callbacks = [];

    /**
     * Register a named callback.
     *
     * @param string $name Callback name (used in template expressions)
     * @param Closure(CallbackParameters): mixed $callback Callback function that receives CallbackParameters and returns mixed
     * @throws InvalidArgumentException If callback name is already registered
     */
    public static function register(string $name, Closure $callback): void
    {
        if (isset(self::$callbacks[$name])) {
            throw new InvalidArgumentException(
                sprintf('Callback "%s" is already registered', $name)
            );
        }

        self::$callbacks[$name] = $callback;
    }

    /**
     * Register a callback, overwriting if it already exists.
     *
     * @param string $name Callback name
     * @param Closure(CallbackParameters): mixed $callback Callback function
     */
    public static function registerOrReplace(string $name, Closure $callback): void
    {
        self::$callbacks[$name] = $callback;
    }

    /**
     * Get a registered callback by name.
     *
     * @param string $name Callback name
     * @return (Closure(CallbackParameters): mixed)|null The callback or null if not found
     */
    public static function get(string $name): ?Closure
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

    /** Clear all registered callbacks. */
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

    /** Get the number of registered callbacks. */
    public static function count(): int
    {
        return count(self::$callbacks);
    }
}
