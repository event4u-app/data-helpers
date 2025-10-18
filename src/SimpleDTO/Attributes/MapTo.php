<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Maps output data to a different key name when converting to array.
 *
 * Supports:
 * - Simple key mapping: #[MapTo('user_name')]
 * - Dot notation for nested output: #[MapTo('user.profile.email')]
 *
 * This attribute is used in toArray() and jsonSerialize() methods
 * to transform property names in the output.
 *
 * Examples:
 *
 * Simple mapping:
 *   #[MapTo('user_name')]
 *   public readonly string $userName;
 *   // Output: ['user_name' => 'John']
 *
 * Nested output:
 *   #[MapTo('user.profile.email')]
 *   public readonly string $email;
 *   // Output: ['user' => ['profile' => ['email' => 'john@example.com']]]
 *
 * Bidirectional mapping (with MapFrom):
 *   #[MapFrom('user_name')]
 *   #[MapTo('user_name')]
 *   public readonly string $userName;
 *   // Input: user_name → userName
 *   // Output: userName → user_name
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapTo
{
    /**
     * @param string $target The target key to map to in output.
     *                       Supports dot notation for nested output.
     */
    public function __construct(
        public readonly string $target,
    ) {
    }
}

