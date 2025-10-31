<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: Value must exist in database table.
 *
 * This is a marker attribute for framework-specific validation (Laravel/Symfony).
 * It does NOT perform validation in LiteDto itself - use a callback attribute for custom validation.
 *
 * Framework support:
 * - Laravel: Converts to 'exists:table,column' or uses Model class
 * - Symfony: Requires Doctrine and custom validation logic
 *
 * Examples:
 * ```php
 * use App\Models\User;
 *
 * class OrderDto extends LiteDto
 * {
 *     public function __construct(
 *         // With Model class
 *         #[Exists(User::class)]
 *         public readonly int $userId,
 *
 *         // With table name
 *         #[Exists('users')]
 *         public readonly int $userId,
 *
 *         // With table and column
 *         #[Exists('users', 'email')]
 *         public readonly string $userEmail,
 *
 *         // With connection
 *         #[Exists('users', 'email', connection: 'tenant')]
 *         public readonly string $email,
 *
 *         // With soft deletes (Laravel)
 *         #[Exists('users', 'email', withoutTrashed: true)]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 *
 * Note: This attribute is only useful when using LiteDto with Laravel or Symfony validators.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Exists
{
    /**
     * @param string $table Database table name or Model class name
     * @param string|null $column Column name (default: property name)
     * @param string|null $connection Database connection name (optional)
     * @param bool $withoutTrashed Exclude soft-deleted records (Laravel only)
     */
    public function __construct(
        public readonly string $table,
        public readonly ?string $column = null,
        public readonly ?string $connection = null,
        public readonly bool $withoutTrashed = false,
    ) {}
}
