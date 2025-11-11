<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;
use InvalidArgumentException;

/**
 * Hash a string value using a specified algorithm.
 *
 * This attribute automatically hashes string values before validation.
 * It does not validate - it transforms the value.
 *
 * Supported algorithms: sha256 (default), sha512, sha1, md5, bcrypt, argon2i, argon2id
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Hash] // Uses sha256 by default
 *         public readonly string $password,
 *
 *         #[Hash('sha512')]
 *         public readonly string $apiKey,
 *
 *         #[Hash('bcrypt')]
 *         public readonly string $securePassword,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Hash implements TransformAttribute
{
    public function __construct(
        public readonly string $algorithm = 'sha256'
    ) {}

    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return match ($this->algorithm) {
            'bcrypt' => password_hash($value, PASSWORD_BCRYPT),
            'argon2i' => password_hash($value, PASSWORD_ARGON2I),
            'argon2id' => password_hash($value, PASSWORD_ARGON2ID),
            // @phpstan-ignore-next-line disallowed.function (Transform attribute for hashing)
            'sha256', 'sha512', 'sha1', 'md5' => hash($this->algorithm, $value),
            default => throw new InvalidArgumentException(
                sprintf(
                    'Unsupported hash algorithm: %s. Supported: sha256, sha512, sha1, md5, bcrypt, argon2i, argon2id',
                    $this->algorithm
                )
            ),
        };
    }
}
