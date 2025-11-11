<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is required unless another field has a specific value.
 *
 * Example:
 * ```php
 * class PaymentDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Required]
 *         #[In(['card', 'cash', 'free'])]
 *         public readonly string $paymentMethod,
 *
 *         #[RequiredUnless('paymentMethod', 'free')]
 *         public readonly ?string $paymentDetails = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredUnless implements ConditionalValidationAttribute, ValidationRule
{
    /**
     * @param string $field Field name to check
     * @param mixed $value Value that makes this field NOT required
     */
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        $value = is_bool($this->value) ? ($this->value ? 'true' : 'false') : $this->value;
        return sprintf('required_unless:%s,%s', $this->field, (string)$value);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $valueStr = is_bool($this->value) ? ($this->value ? 'true' : 'false') : (string)$this->value;
        return sprintf('The attribute field is required unless %s is %s.', $this->field, $valueStr);
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
        // Check if the other field does NOT have the expected value
        if (!isset($allData[$this->field]) || $allData[$this->field] !== $this->value) {
            // Field is required - check if value is present
            return null !== $value && '' !== $value;
        }

        // Field is not required (other field has the expected value)
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
        $valueStr = is_bool($this->value) ? ($this->value ? 'true' : 'false') : (string)$this->value;
        return sprintf('The %s field is required unless %s is %s.', $propertyName, $this->field, $valueStr);
    }
}
