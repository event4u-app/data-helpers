<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Contracts;

/**
 * Interface for validation attributes that can generate Symfony constraints.
 *
 * Validation attributes implementing this interface can provide Symfony-specific
 * constraint objects for use with Symfony Validator.
 *
 * When Symfony Validator is installed, this returns Symfony\Component\Validator\Constraint objects.
 * When Symfony Validator is not installed, this returns DummyConstraint objects.
 *
 * Example:
 * ```php
 * #[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
 * class Email implements ValidationRule, SymfonyConstraint
 * {
 *     use OptionalSymfonyConstraint;
 *
 *     public function rule(): string
 *     {
 *         return 'email';
 *     }
 *
 *     public function constraint(): object|array
 *     {
 *         return $this->createConstraint(fn() => new Assert\Email());
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
     * Returns a Symfony Constraint object when Symfony Validator is installed,
     * or a DummyConstraint object when it's not.
     *
     * @return object|array<object> Constraint object or array of constraint objects
     */
    public function constraint(): object|array;
}
