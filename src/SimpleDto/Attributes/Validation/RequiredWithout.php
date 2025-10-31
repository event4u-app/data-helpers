<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;

/**
 * Conditional validation attribute: Field is required if any of the specified fields are NOT present.
 *
 * Example:
 * ```php
 * class ContactDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly ?string $phone = null,
 *
 *         #[RequiredWithout(['phone'])]
 *         public readonly ?string $email = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredWithout implements ConditionalValidationAttribute
{
    /** @param array<string> $fields Field names that trigger requirement when absent */
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
        // Check if any of the specified fields are missing
        $anyFieldMissing = false;
        foreach ($this->fields as $field) {
            if (!isset($allData[$field]) || null === $allData[$field]) {
                $anyFieldMissing = true;
                break;
            }
        }

        // If all fields are present, this field is not required
        if (!$anyFieldMissing) {
            return true;
        }

        // If any field is missing, this field IS required
        if (null === $value) {
            return false;
        }
        return !(is_string($value) && '' === trim($value));
    }

    public function getErrorMessage(string $propertyName): string
    {
        $fields = implode(', ', $this->fields);
        return sprintf('The %s field is required when %s is not present.', $propertyName, $fields);
    }
}
