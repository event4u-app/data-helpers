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
 * Can be applied to:
 * - Properties/Parameters: Trims that specific property
 * - Class: Trims all string properties (property-level attributes take precedence)
 *
 * Example:
 * ```php
 * // Property-level
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
 *
 * // Class-level (applies to all string properties)
 * #[Trim]
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,        // Will be trimmed
 *         public readonly string $description, // Will be trimmed
 *         public readonly int $price,          // Not affected (not a string)
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
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
