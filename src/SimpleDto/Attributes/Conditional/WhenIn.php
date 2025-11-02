<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use ReflectionException;
use ReflectionObject;

/**
 * Conditional attribute: Include property when value is in a list.
 *
 * Supports two modes:
 * 1. Check property's own value: #[WhenIn(['active', 'pending'])]
 * 2. Check another field's value: #[WhenIn('status', ['active', 'pending'])]
 *
 * Example:
 * ```php
 * class OrderDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $status,
 *
 *         // Check property's own value
 *         #[WhenIn(['completed', 'shipped'])]
 *         public readonly string $status = 'pending',
 *
 *         // Check another field's value
 *         #[WhenIn('status', ['active', 'pending'])]
 *         public readonly ?string $statusData = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenIn implements ConditionalProperty
{
    public readonly ?string $field;
    /** @var array<mixed> */
    public readonly array $values;
    public readonly bool $strict;

    /**
     * @param string|array<mixed> $fieldOrValues Field name to check OR list of allowed values
     * @param array<mixed>|bool|null $valuesOrStrict List of allowed values OR strict flag
     * @param bool $strict Use strict comparison (===)
     */
    public function __construct(
        string|array $fieldOrValues,
        array|bool|null $valuesOrStrict = null,
        bool $strict = true,
    ) {
        // Mode 1: #[WhenIn(['value1', 'value2'])] - check property's own value
        if (is_array($fieldOrValues)) {
            $this->field = null;
            $this->values = $fieldOrValues;
            $this->strict = is_bool($valuesOrStrict) ? $valuesOrStrict : true;
        }
        // Mode 2: #[WhenIn('field', ['value1', 'value2'])] - check another field's value
        else {
            $this->field = $fieldOrValues;
            $this->values = is_array($valuesOrStrict) ? $valuesOrStrict : [];
            $this->strict = $strict;
        }
    }

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Mode 1: Check property's own value
        if (null === $this->field) {
            return in_array($value, $this->values, $this->strict);
        }

        // Mode 2: Check another field's value
        try {
            $reflection = new ReflectionObject($dto);
            if ($reflection->hasProperty($this->field)) {
                $property = $reflection->getProperty($this->field);
                $fieldValue = $property->getValue($dto);
                return in_array($fieldValue, $this->values, $this->strict);
            }
        } catch (ReflectionException) {
            return false;
        }

        return false;
    }
}
