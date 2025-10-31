<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property based on a callback.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *
 *         #[WhenCallback(fn($value, $dto) => $dto->age >= 18)]
 *         public readonly ?string $adultContent = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenCallback implements ConditionalProperty
{
    /** @param callable $callback Callback that receives ($value, $dto, $context) and returns bool */
    public function __construct(
        public readonly mixed $callback,
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
        return (bool)($this->callback)($value, $dto, $context);
    }
}
