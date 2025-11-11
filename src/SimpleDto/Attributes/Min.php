<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate minimum value/length.
 *
 * For strings: minimum length
 * For numbers: minimum value
 * For arrays: minimum number of items
 *
 * Example:
 *   #[Min(3)]
 *   public readonly string $name;
 *
 *   #[Min(18)]
 *   public readonly int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Min implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value) {
            return true; // Null values are handled by Required attribute
        }

        // For strings: check length
        if (is_string($value)) {
            return mb_strlen($value) >= $this->value;
        }

        // For arrays: check count
        if (is_array($value)) {
            return count($value) >= $this->value;
        }

        // For numbers: check value
        if (is_numeric($value)) {
            return $value >= $this->value;
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s must be at least %s.', $propertyName, $this->value);
    }

    public function rule(): string
    {
        return 'min:' . $this->value;
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\GreaterThanOrEqual",
            [
                'value' => $this->value,
                'message' => $this->message,
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
