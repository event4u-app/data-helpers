<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is required if another field has a specific value.
 *
 * Example:
 * ```php
 * class ShippingDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Required]
 *         #[In(['pickup', 'delivery'])]
 *         public readonly string $shippingMethod,
 *
 *         #[RequiredIf('shippingMethod', 'delivery')]
 *         public readonly ?string $address = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredIf implements ValidationRule
{
    /**
     * @param string $field Field name to check
     * @param mixed $value Value that makes this field required
     */
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        $value = is_bool($this->value) ? ($this->value ? 'true' : 'false') : $this->value;
        return sprintf('required_if:%s,%s', $this->field, (string)$value);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $valueStr = is_bool($this->value) ? ($this->value ? 'true' : 'false') : (string)$this->value;
        return sprintf('The attribute field is required when %s is %s.', $this->field, $valueStr);
    }
}

