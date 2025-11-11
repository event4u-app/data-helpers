<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a numeric value is unsigned (>= 0).
 *
 * Useful for MySQL UNSIGNED columns.
 *
 * Example:
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Unsigned]
 *         public readonly int $quantity,
 *
 *         #[Unsigned]
 *         public readonly float $price,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Unsigned implements ValidationAttribute, ValidationRule, SymfonyConstraint
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

        return 0 <= $value;
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s must be unsigned (greater than or equal to 0).', $propertyName);
    }

    public function rule(): string
    {
        return 'min:0';
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\GreaterThanOrEqual",
            [
                'value' => 0,
                'message' => $this->message,
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
