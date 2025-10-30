<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalValidationAttribute;

/**
 * Validation attribute: Value must be different from another field.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $email,
 *
 *         #[Different('email')]
 *         public readonly string $alternativeEmail,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Different implements ConditionalValidationAttribute
{
    /** @param string $field Field name to compare with */
    public function __construct(
        public readonly string $field,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // This method is not used for conditional validation
        // We need access to all data, so validateConditional() is used instead
        return true;
    }

    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // Check if comparison field exists
        if (!isset($allData[$this->field])) {
            // If the comparison field doesn't exist, we consider it different
            return true;
        }

        // Check if values are different
        return $value !== $allData[$this->field];
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s and %s must be different.', $propertyName, $this->field);
    }
}
