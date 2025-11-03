<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is only validated if it is present in the input.
 *
 * This is useful for optional fields that should be validated only when provided.
 * If the field is not present in the input data, all validation rules are skipped.
 *
 * Example:
 * ```php
 * class UpdateUserDto extends SimpleDto
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
class Sometimes implements ValidationRule
{
    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'sometimes';
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return null; // This is a meta-rule, no error message needed
    }
}
