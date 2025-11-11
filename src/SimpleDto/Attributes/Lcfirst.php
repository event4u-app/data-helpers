<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Transform a string to have a lowercase first letter.
 *
 * This attribute automatically converts the first character to lowercase before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class ApiDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Lcfirst]
 *         public readonly string $variableName,
 *
 *         #[Lcfirst]
 *         public readonly string $propertyName,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Lcfirst implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return mb_strtolower(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
    }
}
