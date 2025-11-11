<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * ConfirmedBy validation attribute.
 *
 * Validates that a specific confirmation field exists and matches the original field.
 * This allows you to specify a custom confirmation field name instead of using
 * the default '_confirmed' suffix.
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
class ConfirmedBy implements ConditionalValidationAttribute, ValidationRule
{
    public function __construct(
        private readonly string $confirmationField,
    ) {
    }

    public function rule(): string
    {
        return 'same:' . $this->confirmationField;
    }

    public function message(): ?string
    {
        return null;
    }

    /**
     * Validate the value using Plain PHP (without access to other fields).
     *
     * @param mixed $value The value to validate
     * @param string $propertyName The name of the property being validated
     * @return bool True if valid, false otherwise
     */
    public function validate(mixed $value, string $propertyName): bool
    {
        // Cannot determine if confirmed without other data
        // Always return true here - actual validation happens in validateConditional
        return true;
    }

    /**
     * Validate the value with access to all data.
     *
     * @param mixed $value The value to validate
     * @param string $propertyName The name of the property being validated
     * @param array<string, mixed> $allData All data being validated
     * @return bool True if validation passes, false otherwise
     */
    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Check if confirmation field exists and matches
        if (!isset($allData[$this->confirmationField])) {
            return false; // Confirmation field is missing
        }

        return $value === $allData[$this->confirmationField];
    }

    /**
     * Get validation error message.
     *
     * @param string $propertyName The name of the property being validated
     * @return string The error message
     */
    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s must match %s.', $propertyName, $this->confirmationField);
    }
}
