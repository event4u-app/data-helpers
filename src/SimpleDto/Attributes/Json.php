<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be valid JSON.
 *
 * Example:
 * ```php
 * class ConfigDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Json]
 *         public readonly string $settings,
 *
 *         #[Json]
 *         public readonly string $metadata,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Json implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** Validate the value. */
    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        // Must be a string
        if (!is_string($value)) {
            return false;
        }

        // Try to decode JSON
        json_decode($value);
        return JSON_ERROR_NONE === json_last_error();
    }

    /** Get validation error message. */
    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s must be a valid JSON string.', $propertyName);
    }

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'json';
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Json();
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return 'The attribute must be a valid JSON string.';
    }
}
