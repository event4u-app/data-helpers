<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Enums;

/**
 * Comparison operator enum for conditional properties.
 *
 * Provides type-safe comparison operators for use in conditional attributes
 * like WhenValue and WhenContext.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\Enums\ComparisonOperator;
 *
 * // Use in attributes
 * #[WhenValue('price', ComparisonOperator::GreaterThan, 100)]
 * public readonly ?string $premiumBadge = null;
 *
 * // Perform comparison
 * $result = ComparisonOperator::GreaterThan->compare(150, 100);
 * // Result: true
 *
 * // Parse from string
 * $operator = ComparisonOperator::fromString('>');
 * ```
 */
enum ComparisonOperator: string
{
    case Equal = '=';
    case LooseEqual = '==';
    case StrictEqual = '===';
    case NotEqual = '!=';
    case StrictNotEqual = '!==';
    case GreaterThan = '>';
    case LessThan = '<';
    case GreaterThanOrEqual = '>=';
    case LessThanOrEqual = '<=';

    /**
     * Perform comparison between two values.
     *
     * @param mixed $left The left operand
     * @param mixed $right The right operand
     *
     * @return bool The comparison result
     */
    public function compare(mixed $left, mixed $right): bool
    {
        return match ($this) {
            self::Equal, self::LooseEqual => $left == $right,
            self::StrictEqual => $left === $right,
            self::NotEqual => $left != $right,
            self::StrictNotEqual => $left !== $right,
            self::GreaterThan => $left > $right,
            self::LessThan => $left < $right,
            self::GreaterThanOrEqual => $left >= $right,
            self::LessThanOrEqual => $left <= $right,
        };
    }

    /**
     * Parse a comparison operator from a string.
     *
     * @param string $operator The operator string (e.g., '>', '===', '!=')
     *
     * @return self|null The comparison operator or null if invalid
     */
    public static function fromString(string $operator): ?self
    {
        return match ($operator) {
            '=' => self::Equal,
            '==' => self::LooseEqual,
            '===' => self::StrictEqual,
            '!=' => self::NotEqual,
            '!==' => self::StrictNotEqual,
            '>' => self::GreaterThan,
            '<' => self::LessThan,
            '>=' => self::GreaterThanOrEqual,
            '<=' => self::LessThanOrEqual,
            default => null,
        };
    }

    /**
     * Check if this is a strict comparison operator.
     *
     * @return bool True if strict (=== or !==), false otherwise
     */
    public function isStrict(): bool
    {
        return match ($this) {
            self::StrictEqual, self::StrictNotEqual => true,
            default => false,
        };
    }

    /**
     * Check if this is an equality operator.
     *
     * @return bool True if equality operator (=, ==, ===), false otherwise
     */
    public function isEquality(): bool
    {
        return match ($this) {
            self::Equal, self::LooseEqual, self::StrictEqual => true,
            default => false,
        };
    }

    /**
     * Check if this is an inequality operator.
     *
     * @return bool True if inequality operator (!=, !==), false otherwise
     */
    public function isInequality(): bool
    {
        return match ($this) {
            self::NotEqual, self::StrictNotEqual => true,
            default => false,
        };
    }

    /**
     * Check if this is a relational operator.
     *
     * @return bool True if relational operator (>, <, >=, <=), false otherwise
     */
    public function isRelational(): bool
    {
        return match ($this) {
            self::GreaterThan, self::LessThan, self::GreaterThanOrEqual, self::LessThanOrEqual => true,
            default => false,
        };
    }

    /**
     * Get all available comparison operators.
     *
     * @return array<string> Array of operator strings
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a string is a valid comparison operator.
     *
     * @param string $operator The operator string to check
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $operator): bool
    {
        return self::fromString($operator) instanceof \event4u\DataHelpers\SimpleDto\Enums\ComparisonOperator;
    }
}
