<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Mark a property as required.
 *
 * The property must be present and not null.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Required]
 *         public readonly string $name,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Required implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Required means: not null and not empty string
        if (null === $value) {
            return false;
        }
        return !(is_string($value) && trim($value) === '');
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s field is required.', $propertyName);
    }
}
