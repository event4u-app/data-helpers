<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Attribute to conditionally include a property when context key exists and is not null.
 *
 * @example
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContextNotNull('user')]
 *         public readonly string $welcomeMessage,
 *     ) {}
 * }
 *
 * $dto = new UserDTO('John', 'Welcome back!');
 * $dto->withContext(['user' => $userObject])->toArray();
 * // ['name' => 'John', 'welcomeMessage' => 'Welcome back!']
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContextNotNull implements ConditionalProperty
{
    /** @param string $key Context key to check */
    public function __construct(
        public readonly string $key,
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
        return array_key_exists($this->key, $context) && null !== $context[$this->key];
    }
}

