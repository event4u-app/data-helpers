<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property when context value equals a specific value.
 *
 * This is a shorthand for WhenContext($key, ComparisonOperator::StrictEqual, $value).
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContextEquals('role', 'admin')]
 *         public readonly ?string $adminPanel = null,
 *     ) {}
 * }
 *
 * $dto = UserDto::from(['name' => 'John', 'adminPanel' => '/admin']);
 * $dto->toArray(['role' => 'admin']); // includes adminPanel
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContextEquals implements ConditionalProperty
{
    /**
     * @param string $key Context key to check
     * @param mixed $value Value to compare against
     */
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
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
        return isset($context[$this->key]) && $context[$this->key] === $this->value;
    }
}
