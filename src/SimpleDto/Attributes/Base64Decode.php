<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Decode a Base64 encoded string value.
 *
 * This attribute automatically decodes Base64 encoded string values before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class ApiDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Base64Decode]
 *         public readonly string $token,
 *
 *         #[Base64Decode]
 *         public readonly string $payload,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Base64Decode implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $decoded = base64_decode($value, true);

        // Return original value if decoding failed
        return false !== $decoded ? $decoded : $value;
    }
}
