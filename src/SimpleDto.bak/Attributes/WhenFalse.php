<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property only when it is false.
 *
 * Example:
 * ```php
 * class FeatureDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly bool $isEnabled,
 *
 *         #[WhenFalse]
 *         public readonly bool $isDisabled = false,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenFalse implements ConditionalProperty
{
    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The Dto instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        return false === $value;
    }
}
