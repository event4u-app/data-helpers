<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Hash a string value using MD5.
 *
 * This attribute automatically hashes string values using MD5 before validation.
 * It does not validate - it transforms the value.
 *
 * Note: MD5 is not cryptographically secure. Use #[Hash('sha256')] or #[Hash('bcrypt')] for passwords.
 *
 * Example:
 * ```php
 * class CacheDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Md5]
 *         public readonly string $cacheKey,
 *
 *         #[Md5]
 *         public readonly string $etag,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Md5 implements TransformAttribute
{
    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // @phpstan-ignore-next-line disallowed.function (Transform attribute for MD5 hashing)
        return md5($value);
    }
}
