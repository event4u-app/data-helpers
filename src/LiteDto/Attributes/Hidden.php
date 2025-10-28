<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Exclude property from serialization.
 *
 * Similar to Carapace's #[Hidden] attribute.
 *
 * Example:
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *           #[Hidden]
 *           public readonly string $password,
 *       ) {}
 *   }
 *
 *   $dto->toArray(); // ['name' => 'John'] (password excluded)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Hidden
{
}
