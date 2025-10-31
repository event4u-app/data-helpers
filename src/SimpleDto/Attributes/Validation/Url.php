<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validate that a property is a valid URL.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Url]
 *         public readonly string $website,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Url implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // Value must be a string
        if (!is_string($value)) {
            return false;
        }

        // Validate URL using filter_var
        return false !== filter_var($value, FILTER_VALIDATE_URL);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s field must be a valid URL.', $propertyName);
    }
}
