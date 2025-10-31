<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;

/**
 * Conditional validation attribute: Field is only validated if it is present in the input.
 *
 * This is useful for optional fields that should be validated only when provided.
 * If the field is not present in the input data, all validation rules are skipped.
 *
 * Example:
 * ```php
 * class UpdateUserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Sometimes]
 *         #[Email]
 *         public readonly ?string $email = null,
 *
 *         #[Sometimes]
 *         #[Min(8)]
 *         public readonly ?string $password = null,
 *     ) {}
 * }
 * ```
 *
 * Note: This is a meta-attribute that affects how other validation rules behave.
 * It doesn't implement ValidationAttribute because it's handled specially in the validation logic.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Sometimes
{
    // This is a marker attribute - no methods needed
    // The validation logic in LiteEngine will check for this attribute
}
