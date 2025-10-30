<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validate that a value is NOT in a list of forbidden values.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[NotIn(['admin', 'root', 'system'])]
 *         public readonly string $username,
 *
 *         #[NotIn([0, -1])]
 *         public readonly int $userId,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotIn implements ValidationAttribute
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

        // Check if value is NOT in the forbidden list
        return !in_array($value, $this->values, true);
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
        return sprintf('The %s field must not be one of the following: %s.', $propertyName, $valuesList);
    }
}
