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
 * Mark a property as required.
 *
 * Example:
 *   #[Required]
 *   public readonly string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Required implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'required';
    }

    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\NotBlank(message: $this->message);
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

