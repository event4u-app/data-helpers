<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Convert empty strings and arrays to null.
 *
 * Similar to OtherDto's #[ConvertEmptyToNull] attribute.
 *
 * Example:
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           #[ConvertEmptyToNull]
 *           public readonly ?string $description,
 *       ) {}
 *   }
 *
 *   UserDto::from(['description' => '']); // description will be null
 *
 * Options:
 *   - convertZero: Convert 0, 0.0 to null (default: false)
 *   - convertStringZero: Convert '0' to null (default: false)
 *   - convertFalse: Convert false to null (default: false)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
class ConvertEmptyToNull
{
    public function __construct(
        public readonly bool $convertZero = false,
        public readonly bool $convertStringZero = false,
        public readonly bool $convertFalse = false,
    ) {
    }
}
