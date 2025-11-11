<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validate that a property is a valid ULID (Universally Unique Lexicographically Sortable Identifier).
 *
 * ULID format: 26 characters, case-insensitive, Crockford's Base32 alphabet.
 * Example: 01ARZ3NDEKTSV4RRFFQ69G5FAV
 *
 * Example:
 * ```php
 * class OrderDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Ulid]
 *         public readonly string $orderId,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Ulid implements ValidationAttribute, ValidationRule, SymfonyConstraint
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

        // ULID must be exactly 26 characters
        if (strlen($value) !== 26) {
            return false;
        }

        // ULID uses Crockford's Base32 alphabet (0-9, A-Z excluding I, L, O, U)
        // Case-insensitive
        return 1 === preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s must be a valid ULID.', $propertyName);
    }

    public function rule(): string
    {
        return 'ulid';
    }

    public function constraint(): object|array
    {
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\Ulid",
            [
                'message' => $this->message,
            ]
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
