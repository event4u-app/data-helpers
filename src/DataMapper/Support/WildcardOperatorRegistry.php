<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use Closure;
use event4u\DataHelpers\DataMapper\Support\WildcardOperators\LimitOperator;
use event4u\DataHelpers\DataMapper\Support\WildcardOperators\OffsetOperator;
use event4u\DataHelpers\DataMapper\Support\WildcardOperators\OrderByHandler;
use event4u\DataHelpers\DataMapper\Support\WildcardOperators\WhereClauseFilter;
use InvalidArgumentException;

/**
 * Registry for wildcard operators (WHERE, ORDER BY, GROUP BY, etc.).
 *
 * Allows registration of custom operators that can process wildcard arrays.
 */
class WildcardOperatorRegistry
{
    /**
     * Registered operators.
     *
     * @var array<string, Closure(array<int|string, mixed>, mixed, mixed, array<string, mixed>): array<int|string, mixed>>
     */
    private static array $operators = [];

    /** Whether built-in operators have been registered. */
    private static bool $builtInRegistered = false;

    /**
     * Register a wildcard operator.
     *
     * The handler receives:
     * - array $items - The wildcard array to process
     * - mixed $config - The operator configuration from the template
     * - mixed $sources - Source data for template evaluation
     * - array $aliases - Already resolved aliases
     *
     * The handler must return the processed array.
     *
     * @param string $name Operator name (e.g., 'WHERE', 'ORDER BY', 'GROUP BY')
     * @param Closure(array<int|string, mixed>, mixed, mixed, array<string, mixed>): array<int|string, mixed> $handler Handler function
     */
    public static function register(string $name, Closure $handler): void
    {
        $normalizedName = self::normalizeName($name);
        self::$operators[$normalizedName] = $handler;
    }

    /**
     * Check if an operator is registered.
     *
     * @param string $name Operator name
     * @return bool True if registered
     */
    public static function has(string $name): bool
    {
        self::ensureBuiltInRegistered();
        $normalizedName = self::normalizeName($name);
        return isset(self::$operators[$normalizedName]);
    }

    /**
     * Get an operator handler.
     *
     * @param string $name Operator name
     * @return Closure(array<int|string, mixed>, mixed, mixed, array<string, mixed>): array<int|string, mixed> Handler function
     * @throws InvalidArgumentException If operator not found
     */
    public static function get(string $name): Closure
    {
        self::ensureBuiltInRegistered();
        $normalizedName = self::normalizeName($name);

        if (!isset(self::$operators[$normalizedName])) {
            throw new InvalidArgumentException(sprintf("Wildcard operator '%s' is not registered", $name));
        }

        return self::$operators[$normalizedName];
    }

    /**
     * Get all registered operator names.
     *
     * @return array<string> Operator names
     */
    public static function all(): array
    {
        self::ensureBuiltInRegistered();
        return array_keys(self::$operators);
    }

    /**
     * Unregister an operator.
     *
     * @param string $name Operator name
     */
    public static function unregister(string $name): void
    {
        $normalizedName = self::normalizeName($name);
        unset(self::$operators[$normalizedName]);
    }

    /** Clear all registered operators (useful for testing). */
    public static function clear(): void
    {
        self::$operators = [];
        self::$builtInRegistered = false;
    }

    /**
     * Normalize operator name for consistent lookup.
     *
     * Removes spaces and underscores, converts to uppercase.
     *
     * @param string $name Operator name
     * @return string Normalized name
     */
    private static function normalizeName(string $name): string
    {
        return str_replace([' ', '_'], '', strtoupper($name));
    }

    /** Ensure built-in operators are registered. */
    private static function ensureBuiltInRegistered(): void
    {
        if (self::$builtInRegistered) {
            return;
        }

        // Register WHERE operator
        self::register('WHERE', function(array $items, mixed $config, mixed $sources, array $aliases): array {
            if (!is_array($config)) {
                return $items;
            }
            /** @var array<string, mixed> $config */
            return WhereClauseFilter::filter($items, $config, $sources, $aliases);
        });

        // Register ORDER BY operator (and aliases)
        $orderByHandler = function(array $items, mixed $config, mixed $sources, array $aliases): array {
            if (!is_array($config)) {
                return $items;
            }
            /** @var array<string, string> $config */
            return OrderByHandler::sort($items, $config, $sources, $aliases);
        };

        self::register('ORDER BY', $orderByHandler);
        self::register('ORDER', $orderByHandler);

        // Register LIMIT operator
        self::register('LIMIT', fn(array $items, mixed $config): array => LimitOperator::apply($items, $config));

        // Register OFFSET operator
        self::register('OFFSET', fn(array $items, mixed $config): array => OffsetOperator::apply($items, $config));

        self::$builtInRegistered = true;
    }
}

