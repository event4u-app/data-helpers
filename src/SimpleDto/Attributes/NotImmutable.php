<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Mark DTO or specific properties as mutable (not immutable).
 *
 * By default, SimpleDto uses readonly properties for immutability.
 * This attribute allows you to make the entire DTO or specific properties mutable.
 *
 * Can be applied to:
 * - Class level: Makes all properties mutable
 * - Property level: Makes only that property mutable
 *
 * Performance:
 * - Checked once during reflection scan
 * - Result cached in feature flags
 * - Zero runtime overhead after initial scan
 *
 * Example (Class level):
 *   #[NotImmutable]
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           public string $name,  // Mutable
 *           public int $age,      // Mutable
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['name' => 'John', 'age' => 30]);
 *   $user->name = 'Jane'; // Allowed
 *   $user->age = 31;      // Allowed
 *
 * Example (Property level):
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           public readonly string $name,  // Immutable
 *           #[NotImmutable]
 *           public int $age,                // Mutable
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['name' => 'John', 'age' => 30]);
 *   $user->age = 31;      // Allowed
 *   $user->name = 'Jane'; // Error: Cannot modify readonly property
 *
 * Use cases:
 * - DTOs that need to be modified after creation
 * - Incremental updates to DTOs
 * - DTOs with computed/cached properties that can be updated
 * - Migration from mutable to immutable DTOs (gradual transition)
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotImmutable
{
    public function __construct()
    {
        // No parameters needed
    }
}
