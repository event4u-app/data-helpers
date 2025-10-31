<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Map property to a target key when serializing.
 *
 * Similar to SimpleDto's #[MapTo].
 *
 * Example:
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           #[MapTo('user_name')]
 *           public readonly string $name,
 *       ) {}
 *   }
 *
 *   $dto->toArray(); // ['user_name' => 'John']
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapTo
{
    public function __construct(
        public readonly string $target,
    ) {}
}
