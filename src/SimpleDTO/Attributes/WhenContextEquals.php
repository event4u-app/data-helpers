<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Attribute to conditionally include a property when context value equals a specific value.
 *
 * This is a shorthand for WhenContext($key, $value).
 *
 * @example
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContextEquals('role', 'admin')]
 *         public readonly string $adminPanel,
 *     ) {}
 * }
 *
 * $dto = new UserDTO('John', '/admin');
 * $dto->withContext(['role' => 'admin'])->toArray();
 * // ['name' => 'John', 'adminPanel' => '/admin']
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContextEquals implements ConditionalProperty
{
    /**
     * @param string $key Context key to check
     * @param mixed $value Value to compare
     * @param bool $strict Use strict comparison (===)
     */
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly bool $strict = true,
    ) {}

    /**
     * Check if the property should be included based on context.
     *
     * @param mixed $value Property value
     * @param object $dto DTO instance
     * @param array<string, mixed> $context Context data
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        if (!array_key_exists($this->key, $context)) {
            return false;
        }

        $contextValue = $context[$this->key];

        return $this->strict
            ? $contextValue === $this->value
            : $contextValue == $this->value;
    }
}

