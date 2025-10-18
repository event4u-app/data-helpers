<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must start with one of the given values.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[StartsWith(['http://', 'https://'])]
 *         public readonly string $website,
 *
 *         #[StartsWith('+49')]
 *         public readonly string $germanPhone,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class StartsWith implements ValidationRule, SymfonyConstraint
{
    /**
     * @param string|array<string> $values Value(s) that the field must start with
     */
    public function __construct(
        public readonly string|array $values,
    ) {}

    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        $values = is_array($this->values) ? $this->values : [$this->values];
        return 'starts_with:' . implode(',', $values);
    }

    /**
     * Get Symfony constraint for this validation attribute.
     *
     * @return Constraint
     */
    public function constraint(): Constraint
    {
        $values = is_array($this->values) ? $this->values : [$this->values];
        // Create regex pattern: ^(value1|value2|...)
        $pattern = '/^(' . implode('|', array_map('preg_quote', $values)) . ')/';

        return new Assert\Regex(
            pattern: $pattern,
            message: $this->message()
        );
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */
    public function message(): ?string
    {
        $values = is_array($this->values) ? implode(', ', $this->values) : $this->values;
        return "The attribute must start with one of the following: {$values}.";
    }
}

