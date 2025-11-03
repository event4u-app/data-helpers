<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when value is in a list.
 *
 * Supports two syntaxes:
 * 1. WhenIn(['value1', 'value2']) - Check if property's own value is in list
 * 2. WhenIn('field', ['value1', 'value2']) - Check if another field's value is in list
 *
 * Example 1 (check own value):
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[WhenIn(['active', 'featured'])]
 *         public readonly string $status = 'draft',
 *     ) {}
 * }
 * ```
 *
 * Example 2 (check another field):
 * ```php
 * class OrderDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $status,
 *
 *         #[WhenIn('status', ['completed', 'shipped'])]
 *         public readonly ?string $trackingNumber = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenIn implements ConditionalProperty
{
    private readonly ?string $field;
    /** @var array<mixed> */
    private readonly array $values;

    /**
     * @param string|array<mixed> $fieldOrValues Field name (if 2 params) or values array (if 1 param)
     * @param array<mixed>|bool $valuesOrStrict Values array (if 2 params) or strict flag (if 1 param)
     * @param bool $strict Use strict comparison
     */
    public function __construct(
        string|array $fieldOrValues,
        array|bool $valuesOrStrict = true,
        public readonly bool $strict = true,
    ) {
        // Syntax 1: WhenIn(['value1', 'value2'])
        if (is_array($fieldOrValues)) {
            $this->field = null;
            $this->values = $fieldOrValues;
        }
        // Syntax 2: WhenIn('field', ['value1', 'value2'])
        else {
            $this->field = $fieldOrValues;
            $this->values = is_array($valuesOrStrict) ? $valuesOrStrict : [];
        }
    }

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The Dto instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // If field is specified, check that field's value
        if (null !== $this->field) {
            if (!property_exists($dto, $this->field)) {
                return false;
            }

            $fieldValue = $dto->{$this->field};

            return in_array($fieldValue, $this->values, $this->strict);
        }

        // Otherwise, check the property's own value
        return in_array($value, $this->values, $this->strict);
    }
}
