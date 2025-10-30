<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when context value is in a list.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContextIn('role', ['admin', 'moderator'])]
 *         public readonly ?string $moderationPanel = null,
 *     ) {}
 * }
 *
 * $dto = UserDto::from(['name' => 'John', 'moderationPanel' => '/moderation']);
 * $dto->toArray(['role' => 'admin']); // includes moderationPanel
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContextIn implements ConditionalProperty
{
    /**
     * @param string $key Context key to check
     * @param array<mixed> $values List of allowed values
     */
    public function __construct(
        public readonly string $key,
        public readonly array $values,
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
        return isset($context[$this->key]) && in_array($context[$this->key], $this->values, true);
    }
}
