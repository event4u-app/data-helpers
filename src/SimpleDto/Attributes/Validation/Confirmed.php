<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;

/**
 * Confirmed validation attribute.
 *
 * Validates that a confirmation field exists and matches the original field.
 * Automatically looks for a field with the suffix '_confirmation'.
 *
 * Example:
 * ```php
 * class RegisterDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Confirmed]
 *         public readonly string $password,
 *         public readonly string $password_confirmation,
 *     ) {}
 * }
 * ```
 *
 * For the property 'password', it looks for 'password_confirmation'.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Confirmed implements ConditionalValidationAttribute
{
    /** @param string|null $field Custom confirmation field name (optional) */
    public function __construct(
        public readonly ?string $field = null,
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

        // Determine confirmation field name
        $confirmationField = $this->field ?? $propertyName . '_confirmation';

        // Check if confirmation field exists
        if (!isset($allData[$confirmationField])) {
            return false;
        }

        // Check if values match
        return $value === $allData[$confirmationField];
    }

    public function getErrorMessage(string $propertyName): string
    {
        $confirmationField = $this->field ?? $propertyName . '_confirmation';
        return sprintf('The %s confirmation does not match.', $propertyName);
    }
}
