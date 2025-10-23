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
 * Validate maximum value/length.
 *
 * For strings: maximum length
 * For numbers: maximum value
 * For arrays: maximum number of items
 *
 * Example:
 *   #[Max(255)]
 *   public readonly string $name;
 *
 *   #[Max(120)]
 *   public readonly int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Max implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'max:' . $this->value;
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\LessThanOrEqual(value: $this->value, message: $this->message);
    }
    public function message(): ?string
    {
        return $this->message;
    }
}
