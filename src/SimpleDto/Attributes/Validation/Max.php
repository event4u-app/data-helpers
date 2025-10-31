<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;

/**
 * Validate maximum value/length.
 *
 * - For strings: maximum length
 * - For numbers: maximum value
 * - For arrays: maximum number of items
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Max(100)]
 *         public readonly string $name,
 *
 *         #[Max(120)]
 *         public readonly int $age,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Max implements ValidationAttribute
{
    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null (use Required for null checks)
        if (null === $value) {
            return true;
        }

        // For strings: check length
        if (is_string($value)) {
            return mb_strlen($value) <= $this->value;
        }

        // For arrays: check count
        if (is_array($value)) {
            return count($value) <= $this->value;
        }

        // For numbers: check value
        if (is_numeric($value)) {
            return $value <= $this->value;
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s must not be greater than %s.', $propertyName, $this->value);
    }
}
