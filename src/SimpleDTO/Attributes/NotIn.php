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
 * NotIn validation attribute.
 *
 * Validates that the value is NOT in the given list of values.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[NotIn(['admin', 'root', 'system'])]
 *         public readonly string $username,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotIn implements ValidationRule, SymfonyConstraint
{
    public ?string $message = null;
    use RequiresSymfonyValidator;

    /** @param array<int|string> $values */
    public function __construct(
        private readonly array $values,
    ) {
    }

    public function rule(): string
    {
        return 'not_in:' . implode(',', $this->values);
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Choice(choices: $this->values, message: $this->message, match: false);
    }
    public function message(): ?string
    {
        return null;
    }
}

