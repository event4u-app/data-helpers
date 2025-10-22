<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use Closure;
use event4u\DataHelpers\DataFilter\Operators\BetweenOperator;
use event4u\DataHelpers\DataFilter\Operators\DistinctOperator as DataFilterDistinctOperator;
use event4u\DataHelpers\DataFilter\Operators\LikeOperator as DataFilterLikeOperator;
use event4u\DataHelpers\DataFilter\Operators\LimitOperator as DataFilterLimitOperator;
use event4u\DataHelpers\DataFilter\Operators\NotBetweenOperator;
use event4u\DataHelpers\DataFilter\Operators\OffsetOperator as DataFilterOffsetOperator;
use event4u\DataHelpers\DataFilter\Operators\OrderByOperator as DataFilterOrderByOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereInOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereNotInOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereNotNullOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereNullOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereOperator as DataFilterWhereOperator;
use event4u\DataHelpers\DataFilter\Operators\WildcardOperatorAdapter;
use event4u\DataHelpers\DataMapper\Support\WildcardOperators\GroupByOperator;
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
            throw new InvalidArgumentException('Wildcard operator „' . $name . '" is not registered');
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

        // Register DataFilter operators via adapter
        self::register('WHERE', WildcardOperatorAdapter::adapt(new DataFilterWhereOperator()));
        self::register('LIKE', WildcardOperatorAdapter::adapt(new DataFilterLikeOperator()));
        self::register('BETWEEN', WildcardOperatorAdapter::adapt(new BetweenOperator()));
        self::register('NOT BETWEEN', WildcardOperatorAdapter::adapt(new NotBetweenOperator()));
        self::register('WHERE IN', WildcardOperatorAdapter::adapt(new WhereInOperator()));
        self::register('IN', WildcardOperatorAdapter::adapt(new WhereInOperator()));
        self::register('WHERE NOT IN', WildcardOperatorAdapter::adapt(new WhereNotInOperator()));
        self::register('NOT IN', WildcardOperatorAdapter::adapt(new WhereNotInOperator()));
        self::register('WHERE NULL', WildcardOperatorAdapter::adapt(new WhereNullOperator()));
        self::register('IS NULL', WildcardOperatorAdapter::adapt(new WhereNullOperator()));
        self::register('WHERE NOT NULL', WildcardOperatorAdapter::adapt(new WhereNotNullOperator()));
        self::register('IS NOT NULL', WildcardOperatorAdapter::adapt(new WhereNotNullOperator()));
        self::register('ORDER BY', WildcardOperatorAdapter::adapt(new DataFilterOrderByOperator()));
        self::register('ORDER', WildcardOperatorAdapter::adapt(new DataFilterOrderByOperator()));
        self::register('LIMIT', WildcardOperatorAdapter::adapt(new DataFilterLimitOperator()));
        self::register('OFFSET', WildcardOperatorAdapter::adapt(new DataFilterOffsetOperator()));
        self::register('DISTINCT', WildcardOperatorAdapter::adapt(new DataFilterDistinctOperator()));

        // Register GROUP BY operator (still uses old implementation - not yet refactored)
        self::register('GROUP BY', function(array $items, mixed $config, mixed $sources, array $aliases): array {
            if (!is_array($config)) {
                return $items;
            }
            /** @var array<string, mixed> $config */
            return GroupByOperator::group($items, $config, $sources, $aliases);
        });

        self::$builtInRegistered = true;
    }
}
