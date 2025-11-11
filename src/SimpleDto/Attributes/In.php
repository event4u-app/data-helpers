<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a value is in a list of allowed values.
 *
 * Example:
 *   #[In(['admin', 'user', 'guest'])]
 *   public readonly string $role;
 *
 *   #[In([1, 2, 3, 4, 5])]
 *   public readonly int $rating;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class In implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /** @param array<int|string> $values */
    public function __construct(
        public readonly array $values,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value) {
            return true; // Null values are handled by Required attribute
        }

        return in_array($value, $this->values, true);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf(
            'The %s must be one of: %s.',
            $propertyName,
            implode(', ', $this->values)
        );
    }

    public function rule(): string
    {
        $values = implode(',', $this->values);

        return 'in:' . $values;
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Choice",
            [
                'choices' => $this->values,
                'message' => $this->message,
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
