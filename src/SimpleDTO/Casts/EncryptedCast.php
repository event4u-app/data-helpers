<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;
use Throwable;

/**
 * Cast attribute to encrypted string.
 *
 * Encrypts data when setting and decrypts when getting.
 * Tries encryption libraries in this order:
 * 1. Custom encrypter (if set via setEncrypter())
 * 2. Laravel Encryption (Illuminate\Encryption\Encrypter)
 * 3. Symfony Encryption (sodium_crypto_secretbox)
 * 4. Fallback to base64 encoding (NOT secure, only for development)
 *
 * Example:
 *   protected function casts(): array {
 *       return ['secret' => 'encrypted'];
 *   }
 *
 * Custom Encrypter Example:
 *   EncryptedCast::setEncrypter(new MyCustomEncrypter());
 *   // or with callable:
 *   EncryptedCast::setEncrypter(new class {
 *       public function encrypt($value) { return base64_encode($value); }
 *       public function decrypt($value) { return base64_decode($value); }
 *   });
 */
class EncryptedCast implements CastsAttributes
{
    private static ?object $customEncrypter = null;

    private static ?object $encrypter = null;

    /**
     * Set a custom encrypter.
     *
     * @param object $encrypter Object with encrypt/decrypt methods
     */
    public static function setEncrypter(object $encrypter): void
    {
        self::$customEncrypter = $encrypter;
        self::$encrypter = null; // Reset cached encrypter
    }

    /** Clear the custom encrypter. */
    public static function clearEncrypter(): void
    {
        self::$customEncrypter = null;
        self::$encrypter = null;
    }

    public function get(mixed $value, array $attributes): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        $encrypter = $this->getEncrypter();

        try {
            // Call decrypt method dynamically
            if (!method_exists($encrypter, 'decrypt')) {
                return null;
            }

            return $encrypter->decrypt($value);
        } catch (Throwable) {
            // Decryption failed - return null
            return null;
        }
    }

    public function set(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        $encrypter = $this->getEncrypter();

        try {
            // Call encrypt method dynamically
            if (!method_exists($encrypter, 'encrypt')) {
                return null;
            }

            $encrypted = $encrypter->encrypt($value);

            return is_string($encrypted) ? $encrypted : null;
        } catch (Throwable) {
            // Encryption failed - return null
            return null;
        }
    }

    /** Get or create the encrypter instance. */
    private function getEncrypter(): object
    {
        if (null !== self::$encrypter) {
            return self::$encrypter;
        }

        // Try custom encrypter first
        if (null !== self::$customEncrypter) {
            self::$encrypter = self::$customEncrypter;

            return self::$encrypter;
        }

        // Try Laravel Encryption
        $encrypter = $this->createLaravelEncrypter();
        if (null !== $encrypter) {
            self::$encrypter = $encrypter;

            return self::$encrypter;
        }

        // Try Symfony Encryption
        $encrypter = $this->createSymfonyEncrypter();
        if (null !== $encrypter) {
            self::$encrypter = $encrypter;

            return self::$encrypter;
        }

        // Fallback to base64 (NOT secure, only for development)
        self::$encrypter = $this->createFallbackEncrypter();

        return self::$encrypter;
    }

    /** Try to create Laravel Encrypter. */
    private function createLaravelEncrypter(): ?object
    {
        $encrypterClass = 'Illuminate\\Encryption\\Encrypter';
        if (!class_exists($encrypterClass)) {
            return null;
        }

        // Try to get encryption key from environment
        $key = $_ENV['APP_KEY'] ?? getenv('APP_KEY');
        if (false === $key || '' === $key || !is_string($key)) {
            return null;
        }

        // Remove "base64:" prefix if present
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7));
            if (false === $decoded) {
                return null;
            }
            $key = $decoded;
        }

        try {
            return new $encrypterClass($key, 'AES-256-CBC');
        } catch (Throwable) {
            return null;
        }
    }

    /** Try to create Symfony Encrypter (only if Symfony is available). */
    private function createSymfonyEncrypter(): ?object
    {
        // Only use sodium if Symfony is available
        if (!class_exists('Symfony\\Component\\HttpKernel\\Kernel')) {
            return null;
        }

        // Check for Symfony's sodium encrypter or other encryption components
        if (!function_exists('sodium_crypto_secretbox')) {
            return null;
        }

        $key = $_ENV['APP_KEY'] ?? getenv('APP_KEY');
        if (false === $key || '' === $key || !is_string($key)) {
            return null;
        }

        // Remove "base64:" prefix if present
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7));
            if (false === $decoded) {
                return null;
            }
            $key = $decoded;
        }

        // Ensure key is correct length for sodium (32 bytes)
        $key = hash('sha256', $key, true);

        return new class($key) {
            public function __construct(private readonly string $key)
            {
            }

            public function encrypt(mixed $value): string
            {
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $encrypted = sodium_crypto_secretbox(serialize($value), $nonce, $this->key);

                return base64_encode($nonce . $encrypted);
            }

            public function decrypt(string $value): mixed
            {
                $decoded = base64_decode($value);
                if (false === $decoded) {
                    return null;
                }

                $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

                $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
                if (false === $decrypted) {
                    return null;
                }

                return unserialize($decrypted);
            }
        };
    }

    /** Create fallback encrypter (base64 - NOT secure). */
    private function createFallbackEncrypter(): object
    {
        return new class {
            public function encrypt(mixed $value): string
            {
                return base64_encode(serialize($value));
            }

            public function decrypt(string $value): mixed
            {
                $decoded = base64_decode($value);
                if (false === $decoded) {
                    return null;
                }

                $unserialized = @unserialize($decoded);
                if (false === $unserialized && 'b:0;' !== $decoded) {
                    return null;
                }

                return $unserialized;
            }
        };
    }
}

