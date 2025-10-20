<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;

/**
 * Validation attribute: Value must be unique in database table.
 *
 * Framework-agnostic attribute that can be converted to:
 * - Laravel: 'unique:table,column,except,idColumn'
 * - Symfony: Custom callback constraint (requires Doctrine)
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Unique('users', 'email')]
 *         public readonly string $email,
 *
 *         // For updates, ignore current record
 *         #[Unique('users', 'email', ignore: $this->id)]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 *
 * Note: Symfony support for Unique requires Doctrine and custom validation logic.
 * The constraint() method returns an empty array to indicate this needs special handling.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Unique implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /**
     * @param string $table Database table name
     * @param string $column Column name (default: property name)
     * @param mixed $ignore Value to ignore (usually current record ID)
     * @param string $idColumn ID column name (default: 'id')
     * @param string|null $connection Database connection name (optional)
     */
    public function __construct(
        public readonly string $table,
        public readonly string $column = 'email',
        public readonly mixed $ignore = null,
        public readonly string $idColumn = 'id',
        public readonly ?string $connection = null,
    ) {}

    /**
     * Convert to Laravel validation rule.
     */
    public function rule(): string
    {
        $table = $this->connection ? sprintf('%s.%s', $this->connection, $this->table) : $this->table;
        $rule = sprintf('unique:%s,%s', $table, $this->column);

        if (null !== $this->ignore) {
            $rule .= sprintf(',%s,%s', $this->ignore, $this->idColumn);
        }

        return $rule;
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */
    public function message(): ?string
    {
        return "The attribute has already been taken.";
    }

    /**
     * Get Symfony constraint.
     *
     * Note: Symfony doesn't have a built-in "unique" constraint for arbitrary tables.
     * The UniqueEntity constraint only works on Doctrine entities.
     * This would require custom validation logic with Doctrine to check
     * if a value is unique in the database.
     *
     * For now, we return an empty array to indicate this constraint
     * needs special handling at the application level.
     */
    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        // Symfony doesn't have a built-in "unique" constraint for arbitrary tables
        // UniqueEntity only works on Doctrine entities
        // This needs to be handled with custom validation logic
        // using Doctrine to query the database
        return [];
    }
}

