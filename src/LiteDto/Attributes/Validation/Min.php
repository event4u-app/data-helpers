<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validate minimum value/length.
 *
 * - For strings: minimum length
 * - For numbers: minimum value
 * - For arrays: minimum number of items
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Min(3)]
 *         public readonly string $name,
 *
 *         #[Min(18)]
 *         public readonly int $age,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Min implements ValidationAttribute
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
            return mb_strlen($value) >= $this->value;
        }

        // For arrays: check count
        if (is_array($value)) {
            return count($value) >= $this->value;
        }

        // For numbers: check value
        if (is_numeric($value)) {
            return $value >= $this->value;
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s must be at least %s.', $propertyName, $this->value);
    }
}
