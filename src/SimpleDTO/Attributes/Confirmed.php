<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;

/**
 * Confirmed validation attribute.
 *
 * Validates that a confirmation field exists and matches the original field.
 * Laravel automatically looks for a field with the suffix '_confirmed'.
 * Symfony uses '_confirmation' suffix by default.
 *
 * Example:
 * ```php
 * class RegisterDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Confirmed]
 *         public readonly string $password,
 *         public readonly string $password_confirmation,  // Symfony
 *         // or
 *         public readonly string $password_confirmed,     // Laravel
 *     ) {}
 * }
 * ```
 *
 * For the property 'password':
 * - Laravel looks for 'password_confirmed'
 * - Symfony looks for 'password_confirmation'
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Confirmed implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param string|null $field Custom confirmation field name (optional) */
    public function __construct(
        public readonly ?string $field = null,
    ) {}

    public function rule(): string
    {
        return 'confirmed';
    }

    public function message(): ?string
    {
        return null;
    }

    /**
     * Get Symfony constraint.
     *
     * Note: Symfony doesn't have a built-in "confirmed" constraint.
     * We need to handle this differently - either by using a custom callback
     * or by checking in the DTO validation logic.
     *
     * For now, we return an empty array to indicate this constraint
     * needs special handling.
     */
    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        // Symfony doesn't have a direct "confirmed" constraint
        // This needs to be handled at the DTO validation level
        // by comparing the field with its confirmation field
        return [];
    }
}
