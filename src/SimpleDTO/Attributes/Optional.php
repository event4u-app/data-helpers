<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

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
 * Note: You can also use union type syntax: `Optional|string` instead of `#[Optional]`
 *
 * @example Basic optional property (attribute syntax)
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Optional]
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * $user = UserDTO::fromArray(['name' => 'John']);
 * $user->email->isEmpty();     // true
 * $user->email->isPresent();   // false
 * ```
 *
 * @example Optional property (union type syntax)
 * ```php
 * use event4u\DataHelpers\Support\Optional;
 *
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly Optional|string $email,  // Union type!
 *     ) {}
 * }
 * ```
 *
 * @example Partial updates
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Optional]
 *         public readonly string $name,
 *         #[Optional]
 *         public readonly string $email,
 *         #[Optional]
 *         public readonly int $age,
 *     ) {}
 *
 *     public function partial(): array
 *     {
 *         $data = [];
 *
 *         if ($this->name->isPresent()) {
 *             $data['name'] = $this->name->get();
 *         }
 *
 *         if ($this->email->isPresent()) {
 *             $data['email'] = $this->email->get();
 *         }
 *
 *         if ($this->age->isPresent()) {
 *             $data['age'] = $this->age->get();
 *         }
 *
 *         return $data;
 *     }
 * }
 *
 * // PATCH /users/1 with { "email": "new@example.com" }
 * $updates = UserDTO::fromArray($request->all());
 * $model->update($updates->partial()); // Only updates email
 * ```
 *
 * @example Optional vs Nullable
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Optional]
 *         public readonly string $email,      // Can be missing
 *         public readonly ?string $phone,     // Can be null
 *         #[Optional]
 *         public readonly ?string $bio,       // Can be missing OR null
 *     ) {}
 * }
 *
 * // Missing email, explicit null phone
 * $user = UserDTO::fromArray(['name' => 'John', 'phone' => null]);
 * $user->email->isEmpty();     // true (missing)
 * $user->phone;                // null (explicitly set)
 *
 * // Explicit null bio
 * $user = UserDTO::fromArray(['name' => 'John', 'bio' => null]);
 * $user->bio->isPresent();     // true
 * $user->bio->get();           // null
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Optional
{
    /** @param mixed $default Default value to use when the property is missing */
    public function __construct(
        public mixed $default = null,
    ) {}
}
