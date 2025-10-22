<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Maps input data from a different key name to this property.
 *
 * Supports:
 * - Simple key mapping: #[MapFrom('user_name')]
 * - Dot notation for nested data: #[MapFrom('user.profile.email')]
 * - Multiple sources with fallback: #[MapFrom(['user.email', 'user.mail', 'email'])]
 *
 * When multiple sources are provided, the first existing value is used.
 * This is useful for handling different API responses or data sources.
 *
 * Examples:
 *
 * Simple mapping:
 *   #[MapFrom('user_name')]
 *   public readonly string $userName;
 *
 * Nested data:
 *   #[MapFrom('user.profile.email')]
 *   public readonly string $email;
 *
 * Multiple sources (fallback):
 *   #[MapFrom(['user.email', 'user.mail', 'email'])]
 *   public readonly string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapFrom
{
    /**
     * @param string|array<string> $source The source key(s) to map from.
     *                                      Can be a single string or array of strings for fallback.
     */
    public function __construct(
        public readonly string|array $source,
    ) {
    }
}
