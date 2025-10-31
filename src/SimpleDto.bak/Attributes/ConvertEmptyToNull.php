<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Attribute to convert empty values to null during hydration.
 *
 * This attribute converts empty values (empty strings, empty arrays) to null.
 * Useful when APIs return empty strings or empty arrays for optional fields.
 *
 * By default, the following values are converted to null:
 * - Empty string: ""
 * - Empty array: []
 * - null
 *
 * Optional conversions (disabled by default):
 * - Integer zero (0) - enable with convertZero: true
 * - String zero ("0") - enable with convertStringZero: true
 * - Boolean false - enable with convertFalse: true
 *
 * Can be applied to:
 * - Individual properties (property-level)
 * - The entire class (class-level) - applies to all properties
 *
 * Example (property-level):
 * ```php
 * use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
 *
 * class ProfileDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[ConvertEmptyToNull]
 *         public readonly ?string $bio = null,
 *
 *         #[ConvertEmptyToNull(convertZero: true)]
 *         public readonly ?int $count = null,
 *     ) {}
 * }
 *
 * $profile = ProfileDto::fromArray([
 *     'bio' => '',
 *     'count' => 0,
 * ]);
 *
 * echo $profile->bio;   // null
 * echo $profile->count; // null
 * ```
 *
 * Example (class-level):
 * ```php
 * #[ConvertEmptyToNull]
 * class ProfileDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly ?string $bio = null,
 *         public readonly ?array $tags = null,
 *     ) {}
 * }
 *
 * $profile = ProfileDto::fromArray([
 *     'bio' => '',
 *     'tags' => [],
 * ]);
 *
 * echo $profile->bio;  // null
 * echo $profile->tags; // null
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
class ConvertEmptyToNull
{
    public function __construct(
        public readonly bool $convertZero = false,
        public readonly bool $convertStringZero = false,
        public readonly bool $convertFalse = false,
    ) {}
}
