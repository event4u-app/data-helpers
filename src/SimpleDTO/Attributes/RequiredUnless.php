<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is required unless another field has a specific value.
 *
 * Example:
 * ```php
 * class PaymentDTO extends SimpleDTO
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
class RequiredUnless implements ValidationRule
{
    /**
     * @param string $field Field name to check
     * @param mixed $value Value that makes this field NOT required
     */
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {}

    /**
     * Convert to Laravel validation rule.
     */
    public function rule(): string
    {
        $value = is_bool($this->value) ? ($this->value ? 'true' : 'false') : $this->value;
        return sprintf('required_unless:%s,%s', $this->field, $value);
    }

    /**
     * Get validation error message.
     */
    public function message(): ?string
    {
        return sprintf('The attribute field is required unless %s is %s.', $this->field, $this->value);
    }
}

