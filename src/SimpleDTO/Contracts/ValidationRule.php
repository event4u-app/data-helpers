<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Contracts;

/**
 * Interface for validation rule attributes.
 *
 * Validation rules can be applied as PHP attributes to DTO properties
 * to automatically generate Laravel validation rules.
 *
 * Example:
 *   #[Required]
 *   #[Email]
 *   public readonly string $email;
 */
interface ValidationRule
{
    /**
     * Get the validation rule(s) for this attribute.
     *
     * Returns a string or array of Laravel validation rules.
     * Examples:
     * - 'required'
     * - 'email'
     * - 'min:3'
     * - ['required', 'email', 'max:255']
     *
     * @return string|array<int, string>
     */
    public function rule(): string|array;

    /**
     * Get custom error message for this rule (optional).
     *
     * Returns null to use Laravel's default message.
     *
     * @return string|null
     */
    public function message(): ?string;
}

