<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validate that a value is between min and max.
 *
 * - For strings: length must be between min and max
 * - For numbers: value must be between min and max
 * - For arrays: count must be between min and max
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Between(3, 50)]
 *         public readonly string $name,
 *
 *         #[Between(18, 120)]
 *         public readonly int $age,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Between implements ValidationAttribute
{
    public function __construct(
        public readonly int|float $min,
        public readonly int|float $max,
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
            $length = mb_strlen($value);
            return $length >= $this->min && $length <= $this->max;
        }

        // For arrays: check count
        if (is_array($value)) {
            $count = count($value);
            return $count >= $this->min && $count <= $this->max;
        }

        // For numbers: check value
        if (is_numeric($value)) {
            return $value >= $this->min && $value <= $this->max;
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s must be between %s and %s.', $propertyName, $this->min, $this->max);
    }
}
