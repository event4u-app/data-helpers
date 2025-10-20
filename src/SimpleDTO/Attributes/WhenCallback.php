<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property based on a callback.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *
 *         #[WhenCallback(fn($dto) => $dto->age >= 18)]
 *         public readonly ?string $adultContent = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenCallback implements ConditionalProperty
{
    /** @param callable(mixed, object, array<string, mixed>): bool $callback Callback that determines if property should be included */
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
        $callback = $this->callback;
        if (!is_callable($callback)) {
            return false;
        }

        // @phpstan-ignore argument.type (Callback signature is flexible)
        return (bool)$callback($dto, $value, $context);
    }
}

