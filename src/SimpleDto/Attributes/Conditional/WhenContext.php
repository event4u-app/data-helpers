<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Conditional;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\SimpleDto\Enums\ComparisonOperator;
use InvalidArgumentException;

/**
 * Conditional attribute: Include property based on context value comparison.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContext('role', ComparisonOperator::StrictEqual, 'admin')]
 *         public readonly ?string $adminPanel = null,
 *     ) {}
 * }
 *
 * $dto = UserDto::from(['name' => 'John', 'adminPanel' => '/admin']);
 * $dto->toArray(['role' => 'admin']); // includes adminPanel
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContext implements ConditionalProperty
{
    public readonly ComparisonOperator $comparisonOperator;

    /**
     * @param string $key Context key to check
     * @param string|ComparisonOperator $operator Comparison operator
     * @param mixed $value Value to compare against
     */
    public function __construct(
        public readonly string $key,
        string|ComparisonOperator $operator,
        public readonly mixed $value,
    ) {
        $this->comparisonOperator = is_string($operator)
            ? (ComparisonOperator::fromString($operator) ?? throw new InvalidArgumentException(
                'Invalid comparison operator: ' . $operator
            ))
            : $operator;
    }

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        if (!isset($context[$this->key])) {
            return false;
        }

        return $this->comparisonOperator->compare($context[$this->key], $this->value);
    }
}
