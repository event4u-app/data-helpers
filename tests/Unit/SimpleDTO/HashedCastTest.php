<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\HashedCast;

describe('HashedCast', function(): void {
    describe('Hashing', function(): void {
        it('hashes plain text password', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);

            expect($instance->password)->toBeString()
                ->and($instance->password)->not->toBe('secret123')
                ->and(str_starts_with($instance->password, '$2y$'))->toBeTrue(); // bcrypt hash
        });

        it('verifies hashed password', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);

            expect(HashedCast::verify('secret123', $instance->password))->toBeTrue()
                ->and(HashedCast::verify('wrong', $instance->password))->toBeFalse();
        });

        it('keeps already hashed value as-is', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $hashedPassword = password_hash('secret123', PASSWORD_BCRYPT);
            $instance = $dto::fromArray(['password' => $hashedPassword]);

            expect($instance->password)->toBe($hashedPassword);
        });

        it('handles null values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $password = null,
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => null]);

            expect($instance->password)->toBeNull();
        });
    });

    describe('Algorithms', function(): void {
        it('uses bcrypt by default', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);

            expect(str_starts_with($instance->password, '$2y$'))->toBeTrue();
        });

        it('supports argon2id algorithm', function(): void {
            if (!defined('PASSWORD_ARGON2ID')) {
                $this->markTestSkipped('Argon2id not available');
            }

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed:argon2id'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);

            expect(str_starts_with($instance->password, '$argon2id$'))->toBeTrue();
        });

        it('supports argon2i algorithm', function(): void {
            if (!defined('PASSWORD_ARGON2I')) {
                $this->markTestSkipped('Argon2i not available');
            }

            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed:argon2i'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);

            expect(str_starts_with($instance->password, '$argon2i$'))->toBeTrue();
        });
    });

    describe('Output Cast', function(): void {
        it('returns hashed value in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);
            $array = $instance->toArray();

            expect($array['password'])->toBe($instance->password)
                ->and(str_starts_with((string)$array['password'], '$2y$'))->toBeTrue();
        });

        it('does not expose plain text password', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => 'secret123']);
            $array = $instance->toArray();

            expect($array['password'])->not->toBe('secret123');
        });
    });

    describe('Edge Cases', function(): void {
        it('hashes empty string', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => '']);

            expect($instance->password)->toBeString()
                ->and(str_starts_with($instance->password, '$2y$'))->toBeTrue();
        });

        it('hashes numeric values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $instance = $dto::fromArray(['password' => 12345]);

            expect($instance->password)->toBeString()
                ->and(str_starts_with($instance->password, '$2y$'))->toBeTrue()
                ->and(HashedCast::verify('12345', $instance->password))->toBeTrue();
        });

        it('handles very long passwords', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $password = '',
                ) {}

                protected function casts(): array
                {
                    return ['password' => 'hashed'];
                }
            };

            $longPassword = str_repeat('a', 1000);
            $instance = $dto::fromArray(['password' => $longPassword]);

            expect($instance->password)->toBeString()
                ->and(str_starts_with($instance->password, '$2y$'))->toBeTrue();
        });
    });
});

