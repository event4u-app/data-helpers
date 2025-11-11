<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * NotIn validation attribute.
 *
 * Validates that the value is NOT in the given list of values.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[NotIn(['admin', 'root', 'system'])]
 *         public readonly string $username,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotIn implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    public ?string $message = null;
    use OptionalSymfonyConstraint;

    /** @param array<int|string> $values */
    public function __construct(
        public readonly array $values,
    ) {
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value) {
            return true; // Null values are handled by Required attribute
        }

        return !in_array($value, $this->values, true);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf(
            'The %s must not be one of: %s.',
            $propertyName,
            implode(', ', $this->values)
        );
    }

    public function rule(): string
    {
        return 'not_in:' . implode(',', $this->values);
    }

    public function constraint(): object
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Choice",
            [
                'choices' => $this->values,
                'message' => $this->message,
                'match' => false,
            ]
        );
    }

    public function message(): ?string
    {
        return null;
    }
}
