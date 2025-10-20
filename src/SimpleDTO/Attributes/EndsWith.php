<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must end with one of the given values.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
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
class EndsWith implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param string|array<string> $values Value(s) that the field must end with */
    public function __construct(
        public readonly string|array $values,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        $values = is_array($this->values) ? $this->values : [$this->values];
        return 'ends_with:' . implode(',', $values);
    }

    /** Get Symfony constraint for this validation attribute. */
    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        $values = is_array($this->values) ? $this->values : [$this->values];
        // Create regex pattern: (value1|value2|...)$
        // Use # as delimiter to avoid issues with / in values
        $pattern = '#(' . implode('|', array_map(fn(string $v): string => preg_quote($v, '#'), $values)) . ')$#';

        return new Assert\Regex(pattern: $pattern, message: $this->message());
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $values = is_array($this->values) ? implode(', ', $this->values) : $this->values;
        return sprintf('The attribute must end with one of the following: %s.', $values);
    }
}

