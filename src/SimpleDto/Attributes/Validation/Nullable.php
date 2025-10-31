<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: Field may be null.
 *
 * This explicitly marks a field as nullable for validation purposes.
 * When present, all other validation rules on this field will be skipped if the value is null.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Nullable]
 *         #[Email]
 *         public readonly ?string $email = null,
 *
 *         #[Nullable]
 *         #[Url]
 *         public readonly ?string $website = null,
 *     ) {}
 * }
 * ```
 *
 * Note: This is a meta-attribute that affects how other validation rules behave.
 * It doesn't implement ValidationAttribute because it's handled specially in the validation logic.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Nullable
{
    // This is a marker attribute - no methods needed
    // The validation logic in SimpleEngine will check for this attribute
}
