<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Encode a string value to Base64.
 *
 * This attribute automatically encodes string values to Base64 before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class ApiDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Base64Encode]
 *         public readonly string $token,
 *
 *         #[Base64Encode]
 *         public readonly string $payload,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Base64Encode implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return base64_encode($value);
    }
}
