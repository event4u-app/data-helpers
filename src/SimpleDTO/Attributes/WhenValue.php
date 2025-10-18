<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property based on value comparison.
 *
 * Example:
 * ```php
 * class ProductDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly float $price,
 *         
 *         #[WhenValue('price', '>', 100)]
 *         public readonly ?string $premiumBadge = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenValue implements ConditionalProperty
{
    /**
     * @param string $field Field name to compare
     * @param string $operator Comparison operator (=, !=, >, <, >=, <=)
     * @param mixed $value Value to compare against
     */
    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        public readonly mixed $value,
    ) {}

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     * @return bool
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        if (!property_exists($dto, $this->field)) {
            return false;
        }

        $fieldValue = $dto->{$this->field};

        return match ($this->operator) {
            '=' => $fieldValue == $this->value,
            '==' => $fieldValue == $this->value,
            '===' => $fieldValue === $this->value,
            '!=' => $fieldValue != $this->value,
            '!==' => $fieldValue !== $this->value,
            '>' => $fieldValue > $this->value,
            '<' => $fieldValue < $this->value,
            '>=' => $fieldValue >= $this->value,
            '<=' => $fieldValue <= $this->value,
            default => false,
        };
    }
}

