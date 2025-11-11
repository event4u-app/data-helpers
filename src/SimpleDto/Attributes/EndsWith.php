<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validation attribute: Value must end with one of the given values.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[EndsWith(['.com', '.org', '.net'])]
 *         public readonly string $website,
 *
 *         #[EndsWith('.pdf')]
 *         public readonly string $documentPath,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EndsWith implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /** @param string|array<string> $values Value(s) that the field must end with */
    public function __construct(
        public readonly string|array $values,
    ) {}

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

        $values = is_array($this->values) ? $this->values : [$this->values];

        // Check if value ends with any of the allowed suffixes
        foreach ($values as $suffix) {
            if (str_ends_with($value, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /** Get validation error message. */
    public function getErrorMessage(string $propertyName): string
    {
        $values = is_array($this->values) ? implode(', ', $this->values) : $this->values;
        return sprintf('The %s must end with one of the following: %s.', $propertyName, $values);
    }

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        $values = is_array($this->values) ? $this->values : [$this->values];
        return 'ends_with:' . implode(',', $values);
    }

    /** Get Symfony constraint for this validation attribute. */
    public function constraint(): object
    {
        $values = is_array($this->values) ? $this->values : [$this->values];
        // Create regex pattern: (value1|value2|...)$
        // Use # as delimiter to avoid issues with / in values
        $pattern = '#(' . implode('|', array_map(fn(string $v): string => preg_quote($v, '#'), $values)) . ')$#';

        return $this->createConstraint(
            "\\Symfony\\Component\\Validator\\Constraints\\Regex",
            [
                'pattern' => $pattern,
                'message' => $this->message(),
            ]
        );
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $values = is_array($this->values) ? implode(', ', $this->values) : $this->values;
        return sprintf('The attribute must end with one of the following: %s.', $values);
    }
}
