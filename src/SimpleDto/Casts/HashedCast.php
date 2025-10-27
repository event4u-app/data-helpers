<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;
use event4u\DataHelpers\SimpleDto\Enums\HashAlgorithm;

/**
 * Cast attribute to hashed string (one-way).
 *
 * Hashes plain text values using password_hash().
 * Returns the hashed value as-is when getting (no decryption).
 *
 * Supports algorithms:
 * - bcrypt (default)
 * - argon2i
 * - argon2id
 *
 * Example:
 *   protected function casts(): array {
 *       return ['password' => 'hashed'];
 *       // or with algorithm (enum - recommended):
 *       return ['password' => new HashedCast(HashAlgorithm::Argon2id)];
 *       // or with algorithm (string - backward compatible):
 *       return ['password' => 'hashed:argon2id'];
 *   }
 */
class HashedCast implements CastsAttributes
{
    private readonly HashAlgorithm $hashAlgorithm;

    public function __construct(
        null|string|HashAlgorithm $algorithm = null,
    ) {
        if (null === $algorithm) {
            $this->hashAlgorithm = HashAlgorithm::Bcrypt;
        } elseif (is_string($algorithm)) {
            $this->hashAlgorithm = HashAlgorithm::fromString($algorithm) ?? HashAlgorithm::Bcrypt;
        } else {
            $this->hashAlgorithm = $algorithm;
        }
    }

    public function get(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        // If already hashed (starts with $2y$ for bcrypt, $argon2 for argon2), return as-is
        if (is_string($value) && $this->isHashed($value)) {
            return $value;
        }

        // Convert to string if not already
        if (!is_string($value)) {
            $value = (string)$value;
        }

        // Hash the value
        $algo = $this->getAlgorithm();

        return password_hash($value, $algo);
    }

    public function set(mixed $value, array $attributes): ?string
    {
        // Return the hashed value as-is (no decryption possible)
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    /** Check if a value is already hashed. */
    private function isHashed(string $value): bool
    {
        // Bcrypt hashes start with $2y$
        if (str_starts_with($value, '$2y$')) {
            return true;
        }

        // Argon2i hashes start with $argon2i$
        if (str_starts_with($value, '$argon2i$')) {
            return true;
        }
        // Argon2id hashes start with $argon2id$
        return str_starts_with($value, '$argon2id$');
    }

    /** Get the password hashing algorithm constant. */
    private function getAlgorithm(): string
    {
        return $this->hashAlgorithm->getConstant();
    }

    /**
     * Verify a plain text value against a hashed value.
     *
     * This is a helper method for verifying passwords.
     */
    public static function verify(string $plainText, string $hashedValue): bool
    {
        return password_verify($plainText, $hashedValue);
    }
}
