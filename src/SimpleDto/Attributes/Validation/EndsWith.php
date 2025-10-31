<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;

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
class EndsWith implements ValidationAttribute
{
    /** @var array<string> */
    private readonly array $values;

    /** @param string|array<string> $values Value(s) that the field must end with */
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

        // Check if value ends with any of the given values
        foreach ($this->values as $suffix) {
            if (str_ends_with($value, $suffix)) {
                return true;
            }
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        $valuesList = implode(', ', $this->values);
        return sprintf('The %s field must end with one of the following: %s.', $propertyName, $valuesList);
    }
}
