<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;

/**
 * Validate that a value is in a list of allowed values.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[In(['admin', 'user', 'guest'])]
 *         public readonly string $role,
 *
 *         #[In([1, 2, 3, 4, 5])]
 *         public readonly int $rating,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class In implements ValidationAttribute
{
    /** @param array<int|string|float|bool> $values */
    public function __construct(
        public readonly array $values,
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // Check if value is in the allowed list
        return in_array($value, $this->values, true);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        $valuesList = implode(
            ', ',
            array_map(fn(bool|float|int|string $v): string => is_string($v) ? sprintf(
                "'%s'",
                $v
            ) : (string)$v, $this->values)
        );
        return sprintf('The %s field must be one of the following: %s.', $propertyName, $valuesList);
    }
}
