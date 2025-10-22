<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Enums;

/**
 * Hash algorithm enum for password hashing.
 *
 * Provides type-safe hash algorithms for use in HashedCast.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDTO\Enums\HashAlgorithm;
 *
 * // Use in casts
 * protected function casts(): array {
 *     return [
 *         'password' => new HashedCast(HashAlgorithm::Argon2id),
 *     ];
 * }
 *
 * // Get constant
 * $constant = HashAlgorithm::Bcrypt->getConstant();
 * // Result: PASSWORD_BCRYPT
 *
 * // Parse from string
 * $algo = HashAlgorithm::fromString('argon2id');
 * ```
 */
enum HashAlgorithm: string
{
    case Bcrypt = 'bcrypt';
    case Argon2i = 'argon2i';
    case Argon2id = 'argon2id';

    /**
     * Get the PHP password hashing constant.
     *
     * @return string The PASSWORD_* constant
     */
    public function getConstant(): string
    {
        return match ($this) {
            self::Bcrypt => PASSWORD_BCRYPT,
            self::Argon2i => PASSWORD_ARGON2I,
            self::Argon2id => PASSWORD_ARGON2ID,
        };
    }

    /**
     * Check if this is an Argon variant.
     *
     * @return bool True if Argon2i or Argon2id, false otherwise
     */
    public function isArgon(): bool
    {
        return match ($this) {
            self::Argon2i, self::Argon2id => true,
            self::Bcrypt => false,
        };
    }

    /**
     * Get the hash prefix for this algorithm.
     *
     * @return string The hash prefix (e.g., '$2y$' for bcrypt)
     */
    public function getHashPrefix(): string
    {
        return match ($this) {
            self::Bcrypt => '$2y$',
            self::Argon2i => '$argon2i$',
            self::Argon2id => '$argon2id$',
        };
    }

    /**
     * Parse a hash algorithm from a string.
     *
     * @param string $algorithm The algorithm string (e.g., 'bcrypt', 'argon2id')
     *
     * @return self|null The hash algorithm or null if invalid
     */
    public static function fromString(string $algorithm): ?self
    {
        return match (strtolower($algorithm)) {
            'bcrypt', 'default' => self::Bcrypt,
            'argon2i' => self::Argon2i,
            'argon2id' => self::Argon2id,
            default => null,
        };
    }

    /**
     * Get all available hash algorithms.
     *
     * @return array<string> Array of algorithm strings
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a string is a valid hash algorithm.
     *
     * @param string $algorithm The algorithm string to check
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $algorithm): bool
    {
        return self::fromString($algorithm) instanceof \event4u\DataHelpers\SimpleDTO\Enums\HashAlgorithm;
    }
}
