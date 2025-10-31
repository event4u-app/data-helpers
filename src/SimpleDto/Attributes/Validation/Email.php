<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validate that a property is a valid email address.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Email]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Email implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null (use Required for null checks)
        if (null === $value) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s must be a valid email address.', $propertyName);
    }
}
