<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

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
class Between implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly int|float $min,
        public readonly int|float $max,
        public readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'between:' . $this->min . ',' . $this->max;
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Range(
            notInRangeMessage: $this->message,
            min: $this->min,
            max: $this->max,
        );
    }
    public function message(): ?string
    {
        return $this->message;
    }
}
