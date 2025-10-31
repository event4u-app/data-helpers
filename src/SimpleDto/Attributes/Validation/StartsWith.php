<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validation attribute: Value must start with one of the given values.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
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
class StartsWith implements ValidationAttribute
{
    /** @var array<string> */
    private readonly array $values;

    /** @param string|array<string> $values Value(s) that the field must start with */
    public function __construct(
        string|array $values,
    ) {
        $this->values = is_array($values) ? $values : [$values];
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // Value must be a string
        if (!is_string($value)) {
            return false;
        }

        // Check if value starts with any of the given values
        foreach ($this->values as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        $valuesList = implode(', ', $this->values);
        return sprintf('The %s field must start with one of the following: %s.', $propertyName, $valuesList);
    }
}
