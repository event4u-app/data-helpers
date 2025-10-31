<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Marks a property as optional (can be missing).
 *
 * Optional properties distinguish between:
 * - A value that is explicitly set to null
 * - A value that was not provided at all (missing)
 *
 * This is useful for:
 * - Partial updates (PATCH requests) where you only want to update provided fields
 * - API consistency where null and missing have different meanings
 * - Default values where you want to distinguish between "use null" and "use default"
 *
 * Note: This is an opt-in feature. Properties without #[Optional] are not wrapped,
 * ensuring zero performance overhead when not used.
 *
 * @example Basic optional property
 * ```php
 * use event4u\DataHelpers\SimpleDto\SimpleDto;
 * use event4u\DataHelpers\SimpleDto\Attributes\Optional;
 * use event4u\DataHelpers\Support\Optional as OptionalWrapper;
 *
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Optional]
 *         public readonly OptionalWrapper|string $email,
 *     ) {}
 * }
 *
 * $user = UserDto::from(['name' => 'John']);
 * $user->email->isEmpty();     // true
 * $user->email->isPresent();   // false
 * ```
 *
 * @example Partial updates
 * ```php
 * class UpdateUserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Optional]
 *         public readonly OptionalWrapper|string $name,
 *         #[Optional]
 *         public readonly OptionalWrapper|string $email,
 *         #[Optional]
 *         public readonly OptionalWrapper|string $phone,
 *     ) {}
 * }
 *
 * // Only update email
 * $updates = UpdateUserDto::from(['email' => 'new@example.com']);
 * if ($updates->email->isPresent()) {
 *     $user->email = $updates->email->get();
 * }
 * // name and phone remain unchanged
 * ```
 *
 * @example Combining with nullable
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Optional]
 *         public readonly OptionalWrapper|string $email,      // Can be missing
 *         public readonly ?string $phone,                     // Can be null
 *         #[Optional]
 *         public readonly OptionalWrapper|string|null $bio,   // Can be missing OR null
 *     ) {}
 * }
 *
 * // Missing email, explicit null phone
 * $user = UserDto::from(['name' => 'John', 'phone' => null]);
 * $user->email->isEmpty();     // true (missing)
 * $user->phone;                // null (explicitly set)
 *
 * // Explicit null bio
 * $user = UserDto::from(['name' => 'John', 'bio' => null]);
 * $user->bio->isPresent();     // true
 * $user->bio->get();           // null
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Optional
{
    // This is a marker attribute - no methods needed
    // The hydration logic in SimpleEngine will check for this attribute
}
