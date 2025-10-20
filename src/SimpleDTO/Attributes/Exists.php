<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;

/**
 * Validation attribute: Value must exist in database table.
 *
 * Framework-agnostic attribute that can be converted to:
 * - Laravel: 'exists:table,column'
 * - Symfony: Custom callback constraint (requires Doctrine)
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Exists('users', 'id')]
 *         public readonly int $userId,
 *     ) {}
 * }
 * ```
 *
 * Note: Symfony support for Exists requires Doctrine and custom validation logic.
 * The constraint() method returns an empty array to indicate this needs special handling.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Exists implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /**
     * @param string $table Database table name
     * @param string $column Column name (default: property name)
     * @param string|null $connection Database connection name (optional)
     */
    public function __construct(
        public readonly string $table,
        public readonly string $column = 'id',
        public readonly ?string $connection = null,
    ) {}

    /** Get validation rule. */
    public function rule(): string
    {
        if (null !== $this->connection) {
            return sprintf('exists:%s.%s,%s', $this->connection, $this->table, $this->column);
        }
        return sprintf('exists:%s,%s', $this->table, $this->column);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return null; // Use default Laravel message
    }

    /**
     * Get Symfony constraint.
     *
     * Note: Symfony doesn't have a built-in "exists" constraint.
     * This would require custom validation logic with Doctrine to check
     * if a value exists in the database.
     *
     * For now, we return an empty array to indicate this constraint
     * needs special handling at the application level.
     */
    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        // Symfony doesn't have a built-in "exists" constraint
        // This needs to be handled with custom validation logic
        // using Doctrine to query the database
        return [];
    }
}

