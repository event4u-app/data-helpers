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
    public readonly mixed $value;

    /**
     * @param string $key Context key to check
     * @param string|ComparisonOperator|null $operatorOrValue Comparison operator or value (if 2 params)
     * @param mixed $value Value to compare against (if 3 params)
     */
    public function __construct(
        public readonly string $key,
        string|ComparisonOperator|null $operatorOrValue = null,
        mixed $value = null,
    ) {
        // Case 1: WhenContext('key') - check if key is truthy
        if (null === $operatorOrValue) {
            $this->comparisonOperator = ComparisonOperator::Truthy;
            $this->value = true;
            return;
        }

        // Case 2: WhenContext('key', 'value') - check if key === value
        if (null === $value) {
            $this->comparisonOperator = ComparisonOperator::StrictEqual;
            $this->value = $operatorOrValue;
            return;
        }

        // Case 3: WhenContext('key', '>=', 5) - check if key >= 5
        $this->comparisonOperator = is_string($operatorOrValue)
            ? (ComparisonOperator::fromString($operatorOrValue) ?? throw new InvalidArgumentException(
                'Invalid comparison operator: ' . $operatorOrValue
            ))
            : $operatorOrValue;
        $this->value = $value;
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
