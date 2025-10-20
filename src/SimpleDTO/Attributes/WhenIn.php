<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when value is in a list.
 *
 * Example:
 * ```php
 * class OrderDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $status,
 *         
 *         #[WhenIn(['completed', 'shipped'])]
 *         public readonly ?string $trackingNumber = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenIn implements ConditionalProperty
{
    /**
     * @param array<mixed> $values List of values to check against
     * @param bool $strict Use strict comparison
     */
    public function __construct(
        public readonly array $values,
        public readonly bool $strict = true,
    ) {}

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        return in_array($value, $this->values, $this->strict);
    }
}

