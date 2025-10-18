<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be different from another field.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $email,
 *
 *         #[Different('email')]
 *         public readonly string $alternativeEmail,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Different implements ValidationRule, SymfonyConstraint
{
    /**
     * @param string $field Field name to compare with
     */
    public function __construct(
        public readonly string $field,
    ) {}

    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        return "different:{$this->field}";
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */
    public function message(): ?string
    {
        return "The attribute and {$this->field} must be different.";
    }

    /**
     * Get Symfony constraint.
     *
     * @return Constraint
     */
    public function constraint(): Constraint
    {
        return new Assert\NotEqualTo(
            propertyPath: $this->field,
            message: "The value must not be equal to {{ compared_value }}."
        );
    }
}

