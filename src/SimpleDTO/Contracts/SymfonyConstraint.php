<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Contracts;

use Symfony\Component\Validator\Constraint;

/**
 * Interface for validation attributes that can generate Symfony constraints.
 *
 * Validation attributes implementing this interface can provide Symfony-specific
 * constraint objects for use with Symfony Validator.
 *
 * Example:
 * ```php
 * #[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
 * class Email implements ValidationRule, SymfonyConstraint
 * {
 *     public function rule(): string
 *     {
 *         return 'email';
 *     }
 *
 *     public function constraint(): Constraint
 *     {
 *         return new Assert\Email();
 *     }
 *
 *     public function message(): ?string
 *     {
 *         return null;
 *     }
 * }
 * ```
 */
interface SymfonyConstraint
{
    /**
     * Get Symfony constraint for this validation attribute.
     *
     * Returns a Symfony Constraint object that can be used with Symfony Validator.
     *
     * @return Constraint|Constraint[]
     */
    public function constraint(): Constraint|array;
}
