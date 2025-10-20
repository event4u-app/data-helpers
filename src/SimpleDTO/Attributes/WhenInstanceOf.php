<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when value is instance of a class.
 *
 * Example:
 * ```php
 * class ResponseDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly mixed $data,
 *         
 *         #[WhenInstanceOf(UserDTO::class)]
 *         public readonly mixed $userData = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenInstanceOf implements ConditionalProperty
{
    /** @param string $className Class name to check against */
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

