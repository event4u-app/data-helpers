<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a value is between min and max.
 *
 * For strings: length between min and max
 * For numbers: value between min and max
 * For arrays: number of items between min and max
 *
 * Example:
 *   #[Between(18, 120)]
 *   public readonly int $age;
 *
 *   #[Between(3, 50)]
 *   public readonly string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Between implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    public function __construct(
        public readonly int|float $min,
        public readonly int|float $max,
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
            $length = mb_strlen($value);
            return $length >= $this->min && $length <= $this->max;
        }

        // For arrays: check count
        if (is_array($value)) {
            $count = count($value);
            return $count >= $this->min && $count <= $this->max;
        }

        // For numbers: check value
        if (is_numeric($value)) {
            return $value >= $this->min && $value <= $this->max;
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf(
            'The %s must be between %s and %s.',
            $propertyName,
            $this->min,
            $this->max
        );
    }

    public function rule(): string
    {
        return 'between:' . $this->min . ',' . $this->max;
    }

    public function constraint(): object
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Range",
            [
                'notInRangeMessage' => $this->message,
                'min' => $this->min,
                'max' => $this->max,
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
