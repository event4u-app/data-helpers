<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Transform a string to uppercase.
 *
 * This attribute automatically converts string values to uppercase before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Uppercase]
 *         public readonly string $sku,
 *
 *         #[Uppercase]
 *         public readonly string $countryCode,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Uppercase implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return mb_strtoupper($value, 'UTF-8');
    }
}
