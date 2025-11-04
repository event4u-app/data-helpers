<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;

/**
 * ConfirmedBy validation attribute.
 *
 * Validates that a specific confirmation field exists and matches the original field.
 * This allows you to specify a custom confirmation field name instead of using
 * the default '_confirmation' suffix.
 *
 * Example:
 * ```php
 * class RegisterDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[ConfirmedBy('passwordVerification')]
 *         public readonly string $password,
 *         public readonly string $passwordVerification,
 *     ) {}
 * }
 * ```
 *
 * This is useful when you want to use a different naming convention
 * or when the confirmation field has a different name.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ConfirmedBy implements ConditionalValidationAttribute
{
    public function __construct(
        public readonly string $confirmationField,
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

        // Check if confirmation field exists
        if (!isset($allData[$this->confirmationField])) {
            return false;
        }

        // Check if values match
        return $value === $allData[$this->confirmationField];
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s and %s must match.', $propertyName, $this->confirmationField);
    }
}
