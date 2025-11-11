<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a numeric value matches MySQL DECIMAL(precision, scale) constraints.
 *
 * Example:
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Decimal(10, 2)]  // DECIMAL(10,2) - max 99999999.99
 *         public readonly float $price,
 *
 *         #[Decimal(5, 3)]   // DECIMAL(5,3) - max 99.999
 *         public readonly float $taxRate,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Decimal implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    public function __construct(
        public readonly int $precision,
        public readonly int $scale = 0,
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

        $valueStr = (string)$value;
        $parts = explode('.', $valueStr);
        
        $integerPart = ltrim($parts[0], '-');
        $decimalPart = $parts[1] ?? '';
        
        $maxIntegerDigits = $this->precision - $this->scale;
        
        if (strlen($integerPart) > $maxIntegerDigits) {
            return false;
        }
        return strlen($decimalPart) <= $this->scale;
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf(
            'The %s must be a decimal with at most %d digits and %d decimal places.',
            $propertyName,
            $this->precision,
            $this->scale
        );
    }

    public function rule(): string
    {
        return sprintf('decimal:%d,%d', $this->precision - $this->scale, $this->scale);
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Regex",
            [
                'pattern' => sprintf('/^-?\d{1,%d}(\.\d{1,%d})?$/', $this->precision - $this->scale, $this->scale),
                'message' => $this->message ?? $this->getErrorMessage('value'),
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
