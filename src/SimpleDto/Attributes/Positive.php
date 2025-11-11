<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a numeric value is positive (> 0).
 *
 * Example:
 * ```php
 * class OrderDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Positive]
 *         public readonly int $itemCount,
 *
 *         #[Positive]
 *         public readonly float $totalAmount,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Positive implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    public function __construct(
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value) {
            return true; // Null values are handled by Required attribute
        }

        if (!is_numeric($value)) {
            return false;
        }

        return 0 < $value;
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s must be positive (greater than 0).', $propertyName);
    }

    public function rule(): string
    {
        return 'gt:0';
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Positive",
            [
                'message' => $this->message,
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
