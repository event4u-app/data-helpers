<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when value is instance of a class.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenInstanceOf(\DateTime::class)]
 *         public readonly mixed $createdAt = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenInstanceOf implements ConditionalProperty
{
    /** @param class-string $className Class name to check */
    public function __construct(
        public readonly string $className,
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
        return $value instanceof $this->className;
    }
}
