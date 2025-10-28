<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Convert empty strings and arrays to null.
 *
 * Similar to Carapace's #[ConvertEmptyToNull] attribute.
 *
 * Example:
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           #[ConvertEmptyToNull]
 *           public readonly ?string $description,
 *       ) {}
 *   }
 *
 *   UserDto::from(['description' => '']); // description will be null
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ConvertEmptyToNull
{
}
