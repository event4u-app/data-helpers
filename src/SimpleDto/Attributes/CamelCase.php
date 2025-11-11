<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Transform a string to camelCase.
 *
 * This attribute automatically converts string values to camelCase before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class ApiDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[CamelCase]
 *         public readonly string $fieldName,
 *
 *         #[CamelCase]
 *         public readonly string $propertyKey,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CamelCase implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Convert to camelCase: snake_case or kebab-case to camelCase
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        
        return lcfirst($value);
    }
}
