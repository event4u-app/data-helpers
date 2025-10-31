<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;

/**
 * Validate that a property is a valid UUID.
 *
 * Supports UUID versions 1, 2, 3, 4, and 5.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Uuid]
 *         public readonly string $id,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Uuid implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null
    ) {}

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

        // UUID regex pattern (supports versions 1-5)
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return 1 === preg_match($pattern, $value);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s field must be a valid UUID.', $propertyName);
    }
}
