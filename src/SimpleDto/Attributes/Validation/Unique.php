<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: Value must be unique in database table.
 *
 * This is a marker attribute for framework-specific validation (Laravel/Symfony).
 * It does NOT perform validation in SimpleDto itself - use a callback attribute for custom validation.
 *
 * Framework support:
 * - Laravel: Converts to 'unique:table,column,except,idColumn'
 * - Symfony: Requires Doctrine and custom validation logic
 *
 * Examples:
 * ```php
 * use App\Models\User;
 *
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         // With Model class
 *         #[Unique(User::class, column: 'email')]
 *         public readonly string $email,
 *
 *         // With table name
 *         #[Unique('users', 'email')]
 *         public readonly string $email,
 *
 *         // For updates, ignore current record
 *         #[Unique('users', 'email', ignore: $this->id)]
 *         public readonly string $email,
 *
 *         // With custom ID column
 *         #[Unique('users', 'email', ignore: $this->uuid, idColumn: 'uuid')]
 *         public readonly string $email,
 *
 *         // With connection
 *         #[Unique('users', 'email', connection: 'tenant')]
 *         public readonly string $email,
 *
 *         // With soft deletes (Laravel)
 *         #[Unique('users', 'email', withoutTrashed: true)]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 *
 * Note: This attribute is only useful when using SimpleDto with Laravel or Symfony validators.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Unique
{
    /**
     * @param string $table Database table name or Model class name
     * @param string|null $column Column name (default: property name)
     * @param mixed $ignore Value to ignore (usually current record ID)
     * @param string $idColumn ID column name (default: 'id')
     * @param string|null $connection Database connection name (optional)
     * @param bool $withoutTrashed Exclude soft-deleted records (Laravel only)
     */
    public function __construct(
        public readonly string $table,
        public readonly ?string $column = null,
        public readonly mixed $ignore = null,
        public readonly string $idColumn = 'id',
        public readonly ?string $connection = null,
        public readonly bool $withoutTrashed = false,
    ) {}
}
