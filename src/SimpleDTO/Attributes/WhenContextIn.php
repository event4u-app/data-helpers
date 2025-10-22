<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Attribute to conditionally include a property when context value is in a list.
 *
 * @example
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContextIn('role', ['admin', 'moderator'])]
 *         public readonly string $moderationPanel,
 *     ) {}
 * }
 *
 * $dto = new UserDTO('John', '/moderation');
 * $dto->withContext(['role' => 'admin'])->toArray();
 * // ['name' => 'John', 'moderationPanel' => '/moderation']
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContextIn implements ConditionalProperty
{
    /**
     * @param string $key Context key to check
     * @param array<mixed> $values List of allowed values
     * @param bool $strict Use strict comparison
     */
    public function __construct(
        public readonly string $key,
        public readonly array $values,
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

        return in_array($contextValue, $this->values, $this->strict);
    }
}
