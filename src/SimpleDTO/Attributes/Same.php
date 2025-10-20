<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be the same as another field.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $password,
 *
 *         #[Same('password')]
 *         public readonly string $passwordConfirmation,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Same implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

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
        return "same:{$this->field}";
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */
    public function message(): ?string
    {
        return "The attribute and {$this->field} must match.";
    }

    /**
     * Get Symfony constraint.
     *
     * Note: This returns an empty array because field comparison constraints
     * need access to all fields, which is not available in the Collection constraint context.
     * The validation will fall back to Laravel validator or framework-independent validator.
     *
     * @return Constraint|array
     */
    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        // Return empty array - this constraint needs special handling
        // because it requires access to other fields in the data array
        return [];
    }
}

