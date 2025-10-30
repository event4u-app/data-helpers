<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property only when it is true.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly bool $isActive,
 *
 *         #[WhenTrue]
 *         public readonly bool $isActive = false,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenTrue implements ConditionalProperty
{
    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        return true === $value;
    }
}
