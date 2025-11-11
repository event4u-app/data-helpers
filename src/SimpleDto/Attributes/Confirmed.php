<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Confirmed validation attribute.
 *
 * Validates that a confirmation field exists and matches the original field.
 * Laravel automatically looks for a field with the suffix '_confirmed'.
 * Symfony uses '_confirmation' suffix by default.
 *
 * Example:
 * ```php
 * class RegisterDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Confirmed]
 *         public readonly string $password,
 *         public readonly string $password_confirmation,  // Symfony
 *         // or
 *         public readonly string $password_confirmed,     // Laravel
 *     ) {}
 * }
 * ```
 *
 * For the property 'password':
 * - Laravel looks for 'password_confirmed'
 * - Symfony looks for 'password_confirmation'
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Confirmed implements ConditionalValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /** @param string|null $field Custom confirmation field name (optional) */
    public function __construct(
        public readonly ?string $field = null,
    ) {}

    public function rule(): string
    {
        return 'confirmed';
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
        // Determine confirmation field name
        $confirmationField = $this->field ?? $propertyName . '_confirmation';

        // Check if confirmation field exists and matches
        if (!isset($allData[$confirmationField])) {
            return false; // Confirmation field is missing
        }

        return $value === $allData[$confirmationField];
    }

    /**
     * Get validation error message.
     *
     * @param string $propertyName The name of the property being validated
     * @return string The error message
     */
    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s confirmation does not match.', $propertyName);
    }

    /**
     * Get Symfony constraint.
     *
     * Note: Symfony doesn't have a built-in "confirmed" constraint.
     * We need to handle this differently - either by using a custom callback
     * or by checking in the Dto validation logic.
     *
     * For now, we return an empty array to indicate this constraint
     * needs special handling.
     */
    public function constraint(): object|array
    {
        // Symfony doesn't have a direct "confirmed" constraint
        // This needs to be handled at the Dto validation level
        // by comparing the field with its confirmation field
        return [];
    }
}
