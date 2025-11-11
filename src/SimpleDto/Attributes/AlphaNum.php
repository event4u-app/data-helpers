<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a string contains only alphanumeric characters.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[AlphaNum]
 *         public readonly string $username,
 *
 *         #[AlphaNum]
 *         public readonly string $code,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AlphaNum implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    public function __construct(
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value || '' === $value) {
            return true; // Empty values are handled by Required attribute
        }

        if (!is_string($value)) {
            return false;
        }

        return ctype_alnum($value);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s must contain only alphanumeric characters.', $propertyName);
    }

    public function rule(): string
    {
        return 'alpha_num';
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Regex",
            [
                'pattern' => '/^[a-zA-Z0-9]+$/',
                'message' => $this->message ?? $this->getErrorMessage('value'),
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
