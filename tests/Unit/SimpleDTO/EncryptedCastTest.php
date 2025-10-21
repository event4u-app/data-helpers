<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\EncryptedCast;

// Helper function for test setup
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function setupEncryptedCast(): void
{
    // Load .env file if exists
    $envFile = __DIR__ . '/../../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        /** @phpstan-ignore-next-line unknown */
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }

    // Reset encrypter before each test
    EncryptedCast::clearEncrypter();
}

describe('EncryptedCast', function(): void {
    beforeEach(function(): void {
        // Load .env file if exists
        $envFile = __DIR__ . '/../../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            /** @phpstan-ignore-next-line unknown */
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                    putenv(trim($key) . '=' . trim($value));
                }
            }
        }

        // Reset encrypter before each test
        EncryptedCast::clearEncrypter();
    });

    describe('Encryption/Decryption', function(): void {
        beforeEach(fn() => setupEncryptedCast());

        it('encrypts and decrypts values with fallback encrypter', function(): void {
            // Use custom encrypter to simulate the flow
            $fallbackEncrypter = new class {
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

            EncryptedCast::setEncrypter($fallbackEncrypter);

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            // Encrypt data manually (simulating encrypted input from database)
            $encryptedValue = base64_encode(serialize('my-secret-data'));

            // Create instance with encrypted data
            $instance = $dto::fromArray(['secret' => $encryptedValue]);

            // The value should be decrypted when accessed
            expect($instance->secret)->toBe('my-secret-data');

            // When converted to array, it should be encrypted
            $array = $instance->toArray();
            expect($array['secret'])->toBeString();
            expect($array['secret'])->not()->toBe('my-secret-data');

            // Create new instance from encrypted data
            $instance2 = $dto::fromArray($array);
            expect($instance2->secret)->toBe('my-secret-data');
        });

        it('handles null values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => null]);

            expect($instance->secret)->toBeNull();
        });

        it('returns null when decryption fails', function(): void {
            // Set a custom encrypter that will fail on invalid data
            $failingEncrypter = new class {
                public function encrypt(mixed $value): string
                {
                    return 'encrypted:' . base64_encode((string)$value);
                }

                public function decrypt(string $value): mixed
                {
                    // Only decrypt if it starts with our prefix
                    if (!str_starts_with($value, 'encrypted:')) {
                        throw new Exception('Invalid encrypted data');
                    }

                    return base64_decode(str_replace('encrypted:', '', $value));
                }
            };

            EncryptedCast::setEncrypter($failingEncrypter);

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            // Try to decrypt invalid data (without prefix)
            $instance = $dto::fromArray(['secret' => 'invalid-data']);

            expect($instance->secret)->toBeNull();
        });
    });

    describe('Output Cast', function(): void {
        beforeEach(fn() => setupEncryptedCast());

        it('encrypts value in toArray', function(): void {
            if (!class_exists('Illuminate\Encryption\Encrypter')) {
                $this->markTestSkipped('Laravel Encryption not available');
            }

            // Set up encryption key
            $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => 'my-secret-data']);
            $array = $instance->toArray();

            expect($array['secret'])->not->toBe('my-secret-data');
        })->skip('Laravel Encryption not available in unit tests');

        it('handles null in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => null]);
            $array = $instance->toArray();

            expect($array['secret'])->toBeNull();
        });
    });

    describe('Edge Cases', function(): void {
        beforeEach(fn() => setupEncryptedCast());

        it('handles decryption failures gracefully', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            // Pass an invalid encrypted string
            $instance = $dto::fromArray(['secret' => 'invalid-encrypted-data']);

            expect($instance->secret)->toBeNull();
        });

        it('handles non-string values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => 12345]);

            expect($instance->secret)->toBeNull();
        });

        it('handles empty string', function(): void {
            if (!class_exists('Illuminate\Encryption\Encrypter')) {
                $this->markTestSkipped('Laravel Encryption not available');
            }

            // Set up encryption key
            $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => '']);

            expect($instance->secret)->toBe('');
        })->skip('Laravel Encryption not available in unit tests');
    });

    describe('Custom Encrypter', function(): void {
        beforeEach(fn() => setupEncryptedCast());

        it('allows setting custom encrypter object', function(): void {
            $mockEncrypter = new class {
                public function encrypt(mixed $value): string
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'encrypted:' . $value;
                }

                public function decrypt(string $value): mixed
                {
                    return str_replace('encrypted:', '', $value);
                }
            };

            EncryptedCast::setEncrypter($mockEncrypter);

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => 'encrypted:my-secret']);

            expect($instance->secret)->toBe('my-secret');
        });

        it('allows setting custom encrypter with base64', function(): void {
            $base64Encrypter = new class {
                public function encrypt(mixed $value): string
                {
                    return 'custom:' . base64_encode((string)$value);
                }

                public function decrypt(string $value): mixed
                {
                    if (!str_starts_with($value, 'custom:')) {
                        return $value; // Not encrypted yet
                    }

                    return base64_decode(str_replace('custom:', '', $value));
                }
            };

            EncryptedCast::setEncrypter($base64Encrypter);

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => 'test-data']);

            expect($instance->secret)->toBe('test-data');

            $array = $instance->toArray();
            expect($array['secret'])->toStartWith('custom:');

            // Verify round-trip
            $instance2 = $dto::fromArray($array);
            expect($instance2->secret)->toBe('test-data');
        });

        it('can clear custom encrypter', function(): void {
            $mockEncrypter = new class {
                public function encrypt(mixed $value): string
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'mock:' . $value;
                }

                public function decrypt(string $value): mixed
                {
                    if (!str_starts_with($value, 'mock:')) {
                        return $value;
                    }

                    return str_replace('mock:', '', $value);
                }
            };

            EncryptedCast::setEncrypter($mockEncrypter);

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            $instance = $dto::fromArray(['secret' => 'mock:test-data']);
            expect($instance->secret)->toBe('test-data');

            $array = $instance->toArray();
            expect($array['secret'])->toStartWith('mock:');

            // Clear and use fallback encrypter
            EncryptedCast::clearEncrypter();

            // Set fallback encrypter
            $fallbackEncrypter = new class {
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

                    return @unserialize($decoded) ?: null;
                }
            };

            EncryptedCast::setEncrypter($fallbackEncrypter);

            $encryptedValue = base64_encode(serialize('test-data-2'));
            $instance2 = $dto::fromArray(['secret' => $encryptedValue]);
            expect($instance2->secret)->toBe('test-data-2');

            $array2 = $instance2->toArray();
            expect($array2['secret'])->not()->toStartWith('mock:');
            expect($array2['secret'])->toBeString();
        });

        it('encrypts and decrypts with custom ROT13 encrypter', function(): void {
            $rot13Encrypter = new class {
                public function encrypt(mixed $value): string
                {
                    return str_rot13((string)$value);
                }

                public function decrypt(string $value): mixed
                {
                    return str_rot13($value);
                }
            };

            EncryptedCast::setEncrypter($rot13Encrypter);

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $secret = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['secret' => 'encrypted'];
                }
            };

            // Input is already ROT13 encrypted
            $encryptedInput = 'Uryyb Jbeyq'; // ROT13 of "Hello World"
            $instance = $dto::fromArray(['secret' => $encryptedInput]);

            // ROT13 is symmetric, so decrypting encrypted value gives original
            expect($instance->secret)->toBe('Hello World');

            $array = $instance->toArray();
            expect($array['secret'])->toBe('Uryyb Jbeyq'); // ROT13 of "Hello World"

            // Verify round-trip
            $instance2 = $dto::fromArray($array);
            expect($instance2->secret)->toBe('Hello World');
        });
    });
});

