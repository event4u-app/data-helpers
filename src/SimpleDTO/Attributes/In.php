<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validate that a value is in a list of allowed values.
 *
 * Example:
 *   #[In(['admin', 'user', 'guest'])]
 *   public readonly string $role;
 *
 *   #[In([1, 2, 3, 4, 5])]
 *   public readonly int $rating;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class In implements ValidationRule, SymfonyConstraint
{
    /** @param array<int|string> $values */
    public function __construct(
        private readonly array $values,
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        $values = implode(',', $this->values);

        return 'in:' . $values;
    }


    public function constraint(): Constraint|array
    {
        return new Assert\Choice(
            choices: $this->values,
            message: $this->message
        );
    }
    public function message(): ?string
    {
        return $this->message;
    }
}

