<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mark a property as required.
 *
 * Example:
 *   #[Required]
 *   public readonly string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Required implements ValidationRule, SymfonyConstraint
{
    public function __construct(
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'required';
    }

    public function constraint(): Constraint
    {
        return new Assert\NotBlank(
            message: $this->message
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

