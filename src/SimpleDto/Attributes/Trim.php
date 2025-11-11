<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Trim whitespace from a string.
 *
 * This attribute automatically removes whitespace from the beginning and end of string values.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Trim]
 *         public readonly string $name,
 *
 *         #[Trim]
 *         #[Email]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Trim implements TransformAttribute
{
    public function __construct(
        public readonly ?string $characters = null
    ) {}

    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return null !== $this->characters
            ? trim($value, $this->characters)
            : trim($value);
    }
}
