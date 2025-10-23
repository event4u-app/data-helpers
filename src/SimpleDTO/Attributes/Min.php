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
class Min implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'min:' . $this->value;
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\GreaterThanOrEqual(value: $this->value, message: $this->message);
    }
    public function message(): ?string
    {
        return $this->message;
    }
}
