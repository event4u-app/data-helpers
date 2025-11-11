<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Transform a string to snake_case.
 *
 * This attribute automatically converts string values to snake_case before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class DatabaseDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[SnakeCase]
 *         public readonly string $columnName,
 *
 *         #[SnakeCase]
 *         public readonly string $tableName,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SnakeCase implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Convert to snake_case
        // First, handle camelCase and PascalCase
        $value = (string)preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);
        // Replace spaces and dashes with underscores
        $value = str_replace([' ', '-'], '_', $value);
        // Convert to lowercase
        $value = mb_strtolower($value, 'UTF-8');
        // Remove multiple underscores
        $value = preg_replace('/_+/', '_', $value);

        return $value;
    }
}
