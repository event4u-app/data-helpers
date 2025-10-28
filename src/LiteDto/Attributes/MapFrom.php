<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Map property from a source key.
 *
 * Similar to SimpleDto's #[MapFrom].
 *
 * Example:
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           #[MapFrom('user_name')]
 *           public readonly string $name,
 *       ) {}
 *   }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapFrom
{
    public function __construct(
        public readonly string $source,
    ) {}
}

