<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Map property from a source key or multiple source keys (fallback).
 *
 * Supports:
 * - Single source: #[MapFrom('user_name')]
 * - Multiple sources (fallback): #[MapFrom(['email', 'email_address', 'mail'])]
 * - Dot notation for nested properties: #[MapFrom('user.email')]
 *
 * Example:
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           #[MapFrom('user_name')]
 *           public readonly string $name,
 *           #[MapFrom(['email', 'email_address'])]
 *           public readonly string $email,
 *           #[MapFrom('user.profile.age')]
 *           public readonly int $age,
 *       ) {}
 *   }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapFrom
{
    /** @param string|array<int, string> $source Single source key or array of source keys (fallback) */
    public function __construct(public readonly string|array $source)
    {
    }
}
