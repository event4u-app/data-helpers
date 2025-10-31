<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalValidationAttribute;

/**
 * Conditional validation attribute: Field is required unless another field has a specific value.
 *
 * Example:
 * ```php
 * class PaymentDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Required]
 *         public readonly string $paymentMethod,
 *
 *         #[RequiredUnless('paymentMethod', 'free')]
 *         public readonly ?string $paymentDetails = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredUnless implements ConditionalValidationAttribute
{
    /**
     * @param string $field Field name to check
     * @param mixed $value Value that makes this field NOT required
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
            // If the field doesn't exist, this field IS required
            if (null === $value) {
                return false;
            }
            return !(is_string($value) && '' === trim($value));
        }

        // If the other field has the specified value, this field is NOT required
        if ($allData[$this->field] === $this->value) {
            return true;
        }

        // If the other field doesn't have the specified value, this field IS required
        if (null === $value) {
            return false;
        }
        return !(is_string($value) && '' === trim($value));
    }

    public function getErrorMessage(string $propertyName): string
    {
        $valueStr = is_bool($this->value) ? ($this->value ? 'true' : 'false') : (string)$this->value;
        return sprintf('The %s field is required unless %s is %s.', $propertyName, $this->field, $valueStr);
    }
}
