<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

use InvalidArgumentException;

/**
 * Registry for filter operators.
 *
 * Allows registration of custom operators that can be used in both
 * DataFilter (post-mapping) and QueryBuilder (during mapping).
 */
final class OperatorRegistry
{
    /**
     * Registered operators.
     *
     * @var array<string, OperatorInterface>
     */
    private static array $operators = [];

    /** Whether built-in operators have been registered. */
    private static bool $builtInRegistered = false;

    /**
     * Register an operator.
     *
     * @param OperatorInterface $operator Operator instance
     */
    public static function register(OperatorInterface $operator): void
    {
        $normalizedName = self::normalizeName($operator->getName());
        self::$operators[$normalizedName] = $operator;

        // Register aliases
        foreach ($operator->getAliases() as $alias) {
            $normalizedAlias = self::normalizeName($alias);
            self::$operators[$normalizedAlias] = $operator;
        }
    }

    /**
     * Register multiple operators at once.
     *
     * @param array<int, OperatorInterface> $operators Operator instances
     */
    public static function registerMany(array $operators): void
    {
        foreach ($operators as $operator) {
            self::register($operator);
        }
    }

    /**
     * Check if an operator is registered.
     *
     * @param string $name Operator name
     */
    public static function has(string $name): bool
    {
        self::ensureBuiltInRegistered();
        $normalizedName = self::normalizeName($name);

        return isset(self::$operators[$normalizedName]);
    }

    /**
     * Get an operator.
     *
     * @param string $name Operator name
     * @throws InvalidArgumentException If operator not found
     */
    public static function get(string $name): OperatorInterface
    {
        self::ensureBuiltInRegistered();
        $normalizedName = self::normalizeName($name);

        if (!isset(self::$operators[$normalizedName])) {
            throw new InvalidArgumentException('Operator "' . $name . '" is not registered');
        }

        return self::$operators[$normalizedName];
    }

    /**
     * Get all registered operators.
     *
     * @return array<string, OperatorInterface>
     */
    public static function all(): array
    {
        self::ensureBuiltInRegistered();

        return self::$operators;
    }

    /** Clear all registered operators. */
    public static function clear(): void
    {
        self::$operators = [];
        self::$builtInRegistered = false;
    }

    /** Normalize operator name (uppercase, trim). */
    private static function normalizeName(string $name): string
    {
        return strtoupper(trim($name));
    }

    /** Ensure built-in operators are registered. */
    private static function ensureBuiltInRegistered(): void
    {
        if (self::$builtInRegistered) {
            return;
        }

        // Register built-in operators
        self::registerMany([
            new WhereOperator(),
            new OrderByOperator(),
            new LimitOperator(),
            new OffsetOperator(),
            new DistinctOperator(),
            new LikeOperator(),
            new BetweenOperator(),
            new NotBetweenOperator(),
            new WhereInOperator(),
            new WhereNotInOperator(),
            new WhereNullOperator(),
            new WhereNotNullOperator(),
        ]);

        self::$builtInRegistered = true;
    }
}
