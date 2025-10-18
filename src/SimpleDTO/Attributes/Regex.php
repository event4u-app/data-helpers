<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validate that a value matches a regular expression.
 *
 * Example:
 *   #[Regex('/^[A-Z]{2}[0-9]{4}$/')]
 *   public readonly string $code;
 *
 *   #[Regex('/^[a-z0-9_-]+$/')]
 *   public readonly string $slug;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Regex implements ValidationRule, SymfonyConstraint
{
    public function __construct(
        private readonly string $pattern,
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'regex:' . $this->pattern;
    }


    public function constraint(): Constraint|array
    {
        return new Assert\Regex(
            pattern: $this->pattern,
            message: $this->message
        );
    }
    public function message(): ?string
    {
        return $this->message;
    }
}

