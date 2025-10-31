<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;

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
class RequiredWith implements ConditionalValidationAttribute
{
    /** @param array<string> $fields Field names that trigger requirement */
    public function __construct(
        public readonly array $fields,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // This method is not used for conditional validation
        // It's only here to satisfy the ValidationAttribute interface
        return true;
    }

    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Check if any of the specified fields are present
        $anyFieldPresent = false;
        foreach ($this->fields as $field) {
            if (isset($allData[$field]) && null !== $allData[$field]) {
                $anyFieldPresent = true;
                break;
            }
        }

        // If none of the fields are present, this field is not required
        if (!$anyFieldPresent) {
            return true;
        }

        // If any field is present, this field IS required
        if (null === $value) {
            return false;
        }
        return !(is_string($value) && '' === trim($value));
    }

    public function getErrorMessage(string $propertyName): string
    {
        $fields = implode(', ', $this->fields);
        return sprintf('The %s field is required when %s is present.', $propertyName, $fields);
    }
}
