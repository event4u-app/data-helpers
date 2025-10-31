<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validation attribute: Value must be valid JSON.
 *
 * Example:
 * ```php
 * class ConfigDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Json]
 *         public readonly string $settings,
 *
 *         #[Json]
 *         public readonly string $metadata,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Json implements ValidationAttribute
{
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

        // Try to decode JSON
        json_decode($value);

        // Check if there was an error
        return JSON_ERROR_NONE === json_last_error();
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s field must be a valid JSON string.', $propertyName);
    }
}
