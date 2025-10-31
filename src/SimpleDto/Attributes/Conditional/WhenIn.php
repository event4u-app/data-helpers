<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when value is in a list.
 *
 * Example:
 * ```php
 * class OrderDto extends LiteDto
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
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenIn implements ConditionalProperty
{
    /**
     * @param array<mixed> $values List of allowed values
     * @param bool $strict Use strict comparison (===)
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
