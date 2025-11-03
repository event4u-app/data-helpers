<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;

/**
 * Validate that a value matches a regular expression.
 *
 * Example:
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Regex('/^[A-Z]{2}[0-9]{4}$/')]
 *         public readonly string $code,
 *
 *         #[Regex('/^[a-z0-9_-]+$/')]
 *         public readonly string $slug,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Regex implements ValidationAttribute
{
    public function __construct(
        public readonly string $pattern,
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

        // Validate against pattern
        return 1 === preg_match($this->pattern, $value);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s field format is invalid.', $propertyName);
    }
}
