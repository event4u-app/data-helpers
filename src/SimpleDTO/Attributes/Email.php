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
 * Validate that a property is a valid email address.
 *
 * Example:
 *   #[Email]
 *   public readonly string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Email implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'email';
    }

    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Email(message: $this->message);
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

