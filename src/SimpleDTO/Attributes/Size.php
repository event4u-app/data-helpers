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
 * Validation attribute: Value must have exact size.
 *
 * Works for:
 * - Strings: exact character count
 * - Arrays: exact element count
 * - Files: exact size in kilobytes
 * - Numbers: exact value
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Size(10)]
 *         public readonly string $phoneNumber,  // Must be exactly 10 characters
 *
 *         #[Size(5)]
 *         public readonly array $tags,  // Must have exactly 5 elements
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Size implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param int $size Exact size required */
    public function __construct(
        public readonly int $size,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'size:' . $this->size;
    }

    /** Convert to Symfony constraint. */
    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        $size = 0 < $this->size ? $this->size : null;
        return new Assert\Length(min: $size, max: $size);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return sprintf('The attribute must be %d.', $this->size);
    }
}
