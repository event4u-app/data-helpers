<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalValidationAttribute;

/**
 * Conditional validation attribute: Field is required if another field has a specific value.
 *
 * Example:
 * ```php
 * class ShippingDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Required]
 *         public readonly string $shippingMethod,
 *
 *         #[RequiredIf('shippingMethod', 'delivery')]
 *         public readonly ?string $address = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredIf implements ConditionalValidationAttribute
{
    /**
     * @param string $field Field name to check
     * @param mixed $value Value that makes this field required
     */
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // This method is not used for conditional validation
        // It's only here to satisfy the ValidationAttribute interface
        return true;
    }

    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Check if the other field exists and has the specified value
        if (!isset($allData[$this->field])) {
            // If the field doesn't exist, this field is not required
            return true;
        }

        // If the other field has the specified value, this field is required
        if ($allData[$this->field] === $this->value) {
            // Check if this field is present and not null
            if (null === $value) {
                return false;
            }
            return !(is_string($value) && '' === trim($value));
        }

        // If the other field doesn't have the specified value, this field is not required
        return true;
    }

    public function getErrorMessage(string $propertyName): string
    {
        $valueStr = is_bool($this->value) ? ($this->value ? 'true' : 'false') : (string)$this->value;
        return sprintf('The %s field is required when %s is %s.', $propertyName, $this->field, $valueStr);
    }
}
