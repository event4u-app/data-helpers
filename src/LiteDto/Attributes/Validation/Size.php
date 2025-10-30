<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validation attribute: Value must have exact size.
 *
 * Works for:
 * - Strings: exact character count
 * - Arrays: exact element count
 * - Numbers: exact value
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Size(10)]
 *         public readonly string $phoneNumber,  // Must be exactly 10 characters
 *
 *         #[Size(5)]
 *         public readonly array $tags,  // Must have exactly 5 elements
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Size implements ValidationAttribute
{
    /** @param int $size Exact size required */
    public function __construct(
        public readonly int $size,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // For strings: check character count
        if (is_string($value)) {
            return mb_strlen($value) === $this->size;
        }

        // For arrays: check element count
        if (is_array($value)) {
            return count($value) === $this->size;
        }

        // For numbers: check exact value
        if (is_int($value) || is_float($value)) {
            return $value === $this->size;
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s field must be exactly %d.', $propertyName, $this->size);
    }
}
