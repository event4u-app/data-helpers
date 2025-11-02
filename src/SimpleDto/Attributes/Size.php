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
 * class UserDto extends SimpleDto
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
class Size implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param int $size Exact size required */
    public function __construct(
        public readonly int $size,
    ) {}

    /** Validate the value. */
    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        // String: check character count
        if (is_string($value)) {
            return mb_strlen($value) === $this->size;
        }

        // Array: check element count
        if (is_array($value)) {
            return count($value) === $this->size;
        }

        // Number: check exact value
        if (is_int($value) || is_float($value)) {
            return $value === $this->size;
        }

        return false;
    }

    /** Get validation error message. */
    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s must be %d.', $propertyName, $this->size);
    }

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
