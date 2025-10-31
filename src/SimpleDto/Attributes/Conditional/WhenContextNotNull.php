<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when context key exists and is not null.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContextNotNull('user')]
 *         public readonly ?string $welcomeMessage = null,
 *     ) {}
 * }
 *
 * $dto = UserDto::from(['name' => 'John', 'welcomeMessage' => 'Welcome back!']);
 * $dto->toArray(['user' => $userObject]); // includes welcomeMessage
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
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        return isset($context[$this->key]) && null !== $context[$this->key];
    }
}
