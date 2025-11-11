<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Bidirectional mapping attribute - combines MapFrom and MapTo.
 *
 * This is a convenience attribute that applies the same mapping for both
 * input (MapFrom) and output (MapTo) operations.
 *
 * Example:
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           #[Map('user_name')]
 *           public readonly string $name,
 *           
 *           #[Map('email_address')]
 *           public readonly string $email,
 *       ) {}
 *   }
 *
 * This is equivalent to:
 *   #[MapFrom('user_name'), MapTo('user_name')]
 *   public readonly string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Map
{
    public function __construct(
        public readonly string $key,
    ) {}
}
