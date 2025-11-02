<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;

/**
 * Validation attribute: Value must be the same as another field.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
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
class Same implements ConditionalValidationAttribute, ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param string $field Field name to compare with */
    public function __construct(
        public readonly string $field,
    ) {}

    /** Validate the value (not used - use validateConditional instead). */
    public function validate(mixed $value, string $propertyName): bool
    {
        // This should not be called directly - use validateConditional instead
        return true;
    }

    /** Validate the value against another field. */
    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        // Check if the other field exists
        if (!array_key_exists($this->field, $allData)) {
            return false;
        }

        // Compare values
        return $value === $allData[$this->field];
    }

    /** Get validation error message. */
    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s and %s must match.', $propertyName, $this->field);
    }

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'same:' . $this->field;
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return sprintf('The attribute and %s must match.', $this->field);
    }

    /**
     * Get Symfony constraint.
     *
     * Note: This returns an empty array because field comparison constraints
     * need access to all fields, which is not available in the Collection constraint context.
     * The validation will fall back to Laravel validator or framework-independent validator.
     */
    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        // Return empty array - this constraint needs special handling
        // because it requires access to other fields in the data array
        return [];
    }
}
