<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Attribute to conditionally include a property based on context.
 *
 * The property is included only when the context meets the specified condition.
 *
 * Supports three syntaxes:
 * 1. WhenContext('key') - Include when context key exists
 * 2. WhenContext('key', 'value') - Include when context['key'] === 'value'
 * 3. WhenContext('key', '>', 'value') - Include when context['key'] > 'value'
 *
 * @example Check if context key exists
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContext('includeEmail')]
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * $dto = new UserDTO('John', 'john@example.com');
 * $dto->withContext(['includeEmail' => true])->toArray();
 * // ['name' => 'John', 'email' => 'john@example.com']
 * ```
 *
 * @example Check context value
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenContext('role', 'admin')]
 *         public readonly string $secretKey,
 *     ) {}
 * }
 *
 * $dto = new UserDTO('John', 'secret123');
 * $dto->withContext(['role' => 'admin'])->toArray();
 * // ['name' => 'John', 'secretKey' => 'secret123']
 * ```
 *
 * @example Check with operator
 * ```php
 * class ProductDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly float $price,
 *
 *         #[WhenContext('userLevel', '>=', 5)]
 *         public readonly float $wholesalePrice,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenContext implements ConditionalProperty
{
    /**
     * @param string $key Context key to check
     * @param string|null $operatorOrValue Operator (if 3 params) or value (if 2 params)
     * @param mixed $value Value to compare (only used with 3 parameters)
     */
    public function __construct(
        public readonly string $key,
        public readonly string|null $operatorOrValue = null,
        public readonly mixed $value = null,
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
        // Check if context key exists
        if (!array_key_exists($this->key, $context)) {
            return false;
        }

        $contextValue = $context[$this->key];

        // If no operatorOrValue specified, just check if key exists
        if (null === $this->operatorOrValue) {
            return true;
        }

        // If value is specified (3 parameters), operatorOrValue is the operator
        if (null !== $this->value) {
            return $this->compareWithOperator($contextValue, $this->operatorOrValue, $this->value);
        }

        // Otherwise (2 parameters), operatorOrValue is the value to compare
        return $contextValue === $this->operatorOrValue;
    }

    /**
     * Compare values using an operator.
     *
     * @param mixed $left Left operand
     * @param string $operator Operator
     * @param mixed $right Right operand
     */
    private function compareWithOperator(mixed $left, string $operator, mixed $right): bool
    {
        return match ($operator) {
            '=' => $left == $right,
            '==' => $left == $right,
            '===' => $left === $right,
            '!=' => $left != $right,
            '!==' => $left !== $right,
            '>' => $left > $right,
            '>=' => $left >= $right,
            '<' => $left < $right,
            '<=' => $left <= $right,
            default => false,
        };
    }
}

