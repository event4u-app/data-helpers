<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\SimpleDto\Enums\ComparisonOperator;
use InvalidArgumentException;
use ReflectionException;
use ReflectionProperty;

/**
 * Conditional attribute: Include property based on another property's value comparison.
 *
 * Example:
 * ```php
 * class ProductDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly float $price,
 *
 *         // Using enum (recommended)
 *         #[WhenValue('price', ComparisonOperator::GreaterThan, 100)]
 *         public readonly ?string $premiumBadge = null,
 *
 *         // Using string (backward compatible)
 *         #[WhenValue('price', '>', 100)]
 *         public readonly ?string $premiumBadge2 = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenValue implements ConditionalProperty
{
    public readonly ComparisonOperator $comparisonOperator;

    /**
     * @param string $field Field name to compare
     * @param string|ComparisonOperator $operator Comparison operator
     * @param mixed $value Value to compare against
     */
    public function __construct(
        public readonly string $field,
        string|ComparisonOperator $operator,
        public readonly mixed $value,
    ) {
        $this->comparisonOperator = is_string($operator)
            ? (ComparisonOperator::fromString($operator) ?? throw new InvalidArgumentException(
                'Invalid comparison operator: ' . $operator
            ))
            : $operator;
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
        // Get the field value from the DTO
        try {
            $reflection = new ReflectionProperty($dto, $this->field);
            $fieldValue = $reflection->getValue($dto);
        } catch (ReflectionException) {
            return false;
        }

        // Perform comparison
        return $this->comparisonOperator->compare($fieldValue, $this->value);
    }
}
