<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is required if any of the specified fields are present.
 *
 * Example:
 * ```php
 * class ContactDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly ?string $phone = null,
 *         public readonly ?string $email = null,
 *
 *         #[RequiredWith(['phone', 'email'])]
 *         public readonly ?string $contactPreference = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredWith implements ConditionalValidationAttribute, ValidationRule
{
    /** @param array<string> $fields Field names that trigger requirement */
    public function __construct(
        public readonly array $fields,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'required_with:' . implode(',', $this->fields);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $fields = implode(', ', $this->fields);
        return sprintf('The attribute field is required when %s is present.', $fields);
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
        // Cannot determine if required without other data
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
        // Check if ANY of the specified fields are present
        $anyFieldPresent = false;
        foreach ($this->fields as $field) {
            if (isset($allData[$field]) && null !== $allData[$field] && '' !== $allData[$field]) {
                $anyFieldPresent = true;
                break;
            }
        }

        if ($anyFieldPresent) {
            // Field is required - check if value is present
            return null !== $value && '' !== $value;
        }

        // Field is not required
        return true;
    }

    /**
     * Get validation error message.
     *
     * @param string $propertyName The name of the property being validated
     * @return string The error message
     */
    public function getErrorMessage(string $propertyName): string
    {
        $fields = implode(', ', $this->fields);
        return sprintf('The %s field is required when %s is present.', $propertyName, $fields);
    }
}
