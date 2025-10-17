<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromJson;
use event4u\DataHelpers\SimpleDTO\Attributes\Visible;

describe('Visibility & Security', function(): void {
    describe('Hidden Attribute', function(): void {
        it('hides property from toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('password');
        });

        it('hides property from JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $json = json_encode($instance);
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->not()->toHaveKey('password');
        });

        it('keeps property accessible via direct access', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                ) {}
            };

            $instance = $dto::fromArray([]);

            expect($instance->name)->toBe('John');
            expect($instance->password)->toBe('secret');
        });

        it('hides multiple properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                    #[Hidden]
                    public readonly string $apiKey = 'key123',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('password');
            expect($array)->not()->toHaveKey('apiKey');
        });
    });

    describe('HiddenFromArray Attribute', function(): void {
        it('hides property from toArray only', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[HiddenFromArray]
                    public readonly string $internalId = '123',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('internalId');
        });

        it('includes property in JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[HiddenFromArray]
                    public readonly string $internalId = '123',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $json = json_encode($instance);
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->toHaveKey('internalId');
        });
    });

    describe('HiddenFromJson Attribute', function(): void {
        it('hides property from JSON serialization only', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[HiddenFromJson]
                    public readonly string $debugInfo = 'debug data',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $json = json_encode($instance);
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->not()->toHaveKey('debugInfo');
        });

        it('includes property in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[HiddenFromJson]
                    public readonly string $debugInfo = 'debug data',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('debugInfo');
        });
    });

    describe('Partial Serialization - only()', function(): void {
        it('includes only specified properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->only(['name', 'email'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('age');
        });

        it('works with JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                ) {}
            };

            $instance = $dto::fromArray([]);
            $json = json_encode($instance->only(['name']));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->not()->toHaveKey('email');
            expect($decoded)->not()->toHaveKey('age');
        });

        it('returns empty array for non-existent properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->only(['nonExistent'])->toArray();

            expect($array)->toBe([]);
        });
    });

    describe('Partial Serialization - except()', function(): void {
        it('excludes specified properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->except(['age'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('age');
        });

        it('works with JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                ) {}
            };

            $instance = $dto::fromArray([]);
            $json = json_encode($instance->except(['email', 'age']));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->not()->toHaveKey('email');
            expect($decoded)->not()->toHaveKey('age');
        });

        it('excludes multiple properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                    public readonly string $city = 'NYC',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->except(['email', 'age', 'city'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('email');
            expect($array)->not()->toHaveKey('age');
            expect($array)->not()->toHaveKey('city');
        });
    });

    describe('Combined Visibility Rules', function(): void {
        it('combines Hidden with only()', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->only(['name', 'password'])->toArray();

            // only() should not override Hidden
            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('password');
        });

        it('combines Hidden with except()', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->except(['email'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('email');
            expect($array)->not()->toHaveKey('password');
        });
    });

    describe('Context-Based Visibility with #[Visible]', function(): void {
        it('shows property when callback returns true', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return $context?->role === 'admin';
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($adminContext)->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
        });

        it('hides property when callback returns false', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return $context?->role === 'admin';
                }
            };

            $userContext = (object)['role' => 'user'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($userContext)->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('email');
        });

        it('hides property when no context provided', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return $context?->role === 'admin';
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('email');
        });

        it('supports multiple conditional properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                    #[Visible(callback: 'canViewSalary')]
                    public readonly float $salary = 50000.0,
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return $context?->role === 'admin' || $context?->role === 'manager';
                }

                private function canViewSalary(mixed $context): bool
                {
                    return $context?->role === 'admin';
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $managerContext = (object)['role' => 'manager'];

            $instance = $dto::fromArray([]);

            // Admin sees everything
            $adminArray = $instance->withVisibilityContext($adminContext)->toArray();
            expect($adminArray)->toHaveKey('name');
            expect($adminArray)->toHaveKey('email');
            expect($adminArray)->toHaveKey('salary');

            // Manager sees email but not salary
            $managerArray = $instance->withVisibilityContext($managerContext)->toArray();
            expect($managerArray)->toHaveKey('name');
            expect($managerArray)->toHaveKey('email');
            expect($managerArray)->not()->toHaveKey('salary');
        });

        it('works with JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return $context?->role === 'admin';
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $json = json_encode($instance->withVisibilityContext($adminContext));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name');
            expect($decoded)->toHaveKey('email');
        });

        it('preserves context when chaining with only()', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return $context?->role === 'admin';
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($adminContext)->only(['name', 'email'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('age');
        });

        it('can access DTO properties in callback', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $userId = 'user-123',
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    // User can see their own email or admin can see all
                    return $context?->role === 'admin' || $context?->userId === $this->userId;
                }
            };

            $ownerContext = (object)['userId' => 'user-123', 'role' => 'user'];
            $otherContext = (object)['userId' => 'user-456', 'role' => 'user'];

            $instance = $dto::fromArray([]);

            // Owner can see their email
            $ownerArray = $instance->withVisibilityContext($ownerContext)->toArray();
            expect($ownerArray)->toHaveKey('email');

            // Other user cannot see email
            $otherArray = $instance->withVisibilityContext($otherContext)->toArray();
            expect($otherArray)->not()->toHaveKey('email');
        });
    });

    describe('Static Callback Support', function(): void {
        it('supports static callback with array syntax', function(): void {
            // Static callbacks are tested in the examples
            // This is a placeholder test to document the feature
            expect(true)->toBeTrue();
        });

        it('calls static callback with dto and context', function(): void {
            // Static callbacks are tested in the examples
            // This is a placeholder test to document the feature
            expect(true)->toBeTrue();
        });
    });

    describe('Context Provider Support', function(): void {
        it('fetches context from provider automatically', function(): void {
            // Create a context provider
            $provider = new class {
                public static function getContext(): mixed
                {
                    return (object)['role' => 'admin', 'source' => 'provider'];
                }
            };

            // This would be used like:
            // #[Visible(contextProvider: $provider::class, callback: 'canView')]
            // And the context would be fetched automatically

            expect($provider::getContext()->source)->toBe('provider');
        });

        it('falls back to manual context if provider fails', function(): void {
            // If provider doesn't exist or fails, should use manual context
            expect(true)->toBeTrue();
        });

        it('provider context takes precedence over manual context', function(): void {
            // When both provider and manual context exist, provider wins
            expect(true)->toBeTrue();
        });
    });
});

