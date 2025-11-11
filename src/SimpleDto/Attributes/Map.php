<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Bidirectional mapping attribute - combines MapFrom and MapTo.
 *
 * This is a convenience attribute that applies the same mapping for both
 * input (MapFrom) and output (MapTo) operations.
 *
 * Supports:
 * - Single source/target: #[Map('user_name')]
 * - Multiple sources with fallback (input only): #[Map(['email', 'email_address', 'mail'])]
 * - Dot notation for nested properties: #[Map('user.email')]
 *
 * Example:
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           #[Map('user_name')]
 *           public readonly string $name,
 *           
 *           #[Map('email_address')]
 *           public readonly string $email,
 *           
 *           #[Map('user.profile.age')]
 *           public readonly int $age,
 *       ) {}
 *   }
 *
 * This is equivalent to:
 *   #[MapFrom('user_name'), MapTo('user_name')]
 *   public readonly string $name;
 *
 * Note: When using array of sources for fallback, only the first source
 * will be used for output mapping (MapTo).
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Map
{
    /** @param string|array<int, string> $key Single key or array of keys (fallback for input) */
    public function __construct(public readonly string|array $key)
    {
    }
}
