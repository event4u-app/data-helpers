<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when it equals a specific value.
 *
 * Example:
 * ```php
 * class OrderDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $status,
 *
 *         #[WhenEquals('completed')]
 *         public readonly ?string $completedAt = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenEquals implements ConditionalProperty
{
    /**
     * @param mixed $value Value to compare against
     * @param bool $strict Use strict comparison (===)
     */
    public function __construct(
        public readonly mixed $value,
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
        if ($this->strict) {
            return $value === $this->value;
        }

        return $value == $this->value;
    }
}
