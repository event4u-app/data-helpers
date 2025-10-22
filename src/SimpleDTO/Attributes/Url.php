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
 * Validate that a property is a valid URL.
 *
 * Example:
 *   #[Url]
 *   public readonly string $website;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Url implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'url';
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Url();
    }
    public function message(): ?string
    {
        return $this->message;
    }
}
