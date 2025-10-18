<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
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
    /**
     * @param int $size Exact size required
     */
    public function __construct(
        public readonly int $size,
    ) {}

    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        return "size:{$this->size}";
    }

    /**
     * Convert to Symfony constraint.
     *
     * @return Constraint
     */
    public function constraint(): Constraint
    {
        return new Assert\Length(
            min: $this->size,
            max: $this->size
        );
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */
    public function message(): ?string
    {
        return "The attribute must be {$this->size}.";
    }
}

