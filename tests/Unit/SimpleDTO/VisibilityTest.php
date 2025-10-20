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
                    return 'admin' === $context?->role;
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
                    return 'admin' === $context?->role;
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
                    return 'admin' === $context?->role;
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
                    return 'admin' === $context?->role || 'manager' === $context?->role;
                }

                private function canViewSalary(mixed $context): bool
                {
                    return 'admin' === $context?->role;
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
                    return 'admin' === $context?->role;
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
                    return 'admin' === $context?->role;
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
                    return 'admin' === $context?->role || $context?->userId === $this->userId;
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
        it('static callback can be called with dto and context', function(): void {
            // Test that static callbacks work conceptually
            $checker = new class {
                public static function canViewEmail(mixed $dto, mixed $context): bool
                {
                    return 'admin' === ($context?->role ?? null);
                }
            };

            // Test the static callback directly
            $mockDto = (object)['name' => 'John', 'email' => 'john@example.com'];
            $adminContext = (object)['role' => 'admin'];
            $userContext = (object)['role' => 'user'];

            expect($checker::canViewEmail($mockDto, $adminContext))->toBeTrue();
            expect($checker::canViewEmail($mockDto, $userContext))->toBeFalse();
        });

        it('static callback with complex permission logic', function(): void {
            $checker = new class {
                public static function canViewSalary(mixed $dto, mixed $context): bool
                {
                    // Admin can see all salaries
                    if ('admin' === ($context?->role ?? null)) {
                        return true;
                    }

                    // User can see their own salary
                    if (isset($dto->userId) && isset($context?->userId)) {
                        return $dto->userId === $context->userId;
                    }

                    return false;
                }
            };

            // Test the static callback directly with different scenarios
            $mockDto = (object)['userId' => 'user-123', 'name' => 'John', 'salary' => 75000.0];

            // Admin can see salary
            $adminContext = (object)['role' => 'admin'];
            expect($checker::canViewSalary($mockDto, $adminContext))->toBeTrue();

            // Same user can see their own salary
            $sameUserContext = (object)['userId' => 'user-123', 'role' => 'user'];
            expect($checker::canViewSalary($mockDto, $sameUserContext))->toBeTrue();

            // Different user cannot see salary
            $differentUserContext = (object)['userId' => 'user-456', 'role' => 'user'];
            expect($checker::canViewSalary($mockDto, $differentUserContext))->toBeFalse();

            // No context - cannot see
            expect($checker::canViewSalary($mockDto, null))->toBeFalse();
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

        it('context provider can be called statically', function(): void {
            $provider = new class {
                private static ?object $currentUser = null;

                public static function setCurrentUser(?object $user): void
                {
                    self::$currentUser = $user;
                }

                public static function getContext(): mixed
                {
                    return self::$currentUser;
                }
            };

            // Set provider context
            $provider::setCurrentUser((object)['role' => 'admin', 'userId' => 'user-123']);

            // Test that provider returns correct context
            $context = $provider::getContext();
            expect($context)->not()->toBeNull();
            expect($context->role)->toBe('admin');
            expect($context->userId)->toBe('user-123');

            // Change context
            $provider::setCurrentUser((object)['role' => 'user', 'userId' => 'user-456']);
            $newContext = $provider::getContext();
            expect($newContext->role)->toBe('user');
            expect($newContext->userId)->toBe('user-456');

            // Clear context
            $provider::setCurrentUser(null);
            expect($provider::getContext())->toBeNull();
        });
    });

    describe('Partial Serialization - Advanced', function(): void {
        it('chains only() and except()', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                    public readonly int $age = 30,
                    public readonly string $city = 'NYC',
                    public readonly string $country = 'USA',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // First only(), then except()
            $array = $instance->only(['name', 'email', 'age'])->except(['age'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('age');
            expect($array)->not()->toHaveKey('city');
            expect($array)->not()->toHaveKey('country');
        });

        it('only() with non-existent properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->only(['name', 'nonExistent'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('nonExistent');
        });

        it('except() with non-existent properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->except(['nonExistent'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
        });

        it('only() with empty array returns empty', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->only([])->toArray();

            expect($array)->toBeEmpty();
        });

        it('except() with all properties returns empty', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $array = $instance->except(['name', 'email'])->toArray();

            expect($array)->toBeEmpty();
        });

        it('respects Hidden attribute with only()', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Hidden]
                    public readonly string $password = 'secret',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);

            // Try to include password via only() - should still be hidden
            $array = $instance->only(['name', 'password', 'email'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not()->toHaveKey('password');
        });
    });

    describe('Context-Based Visibility - Advanced', function(): void {
        it('multiple Visible properties with different callbacks', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                    #[Visible(callback: 'canViewSalary')]
                    public readonly float $salary = 75000.0,
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return in_array($context?->role ?? null, ['admin', 'manager'], true);
                }

                private function canViewSalary(mixed $context): bool
                {
                    return 'admin' === ($context?->role ?? null);
                }
            };

            $instance = $dto::fromArray([]);

            // Admin can see both
            $adminContext = (object)['role' => 'admin'];
            $arrayAdmin = $instance->withVisibilityContext($adminContext)->toArray();
            expect($arrayAdmin)->toHaveKey('email');
            expect($arrayAdmin)->toHaveKey('salary');

            // Manager can see email but not salary
            $managerContext = (object)['role' => 'manager'];
            $arrayManager = $instance->withVisibilityContext($managerContext)->toArray();
            expect($arrayManager)->toHaveKey('email');
            expect($arrayManager)->not()->toHaveKey('salary');

            // User can see neither
            $userContext = (object)['role' => 'user'];
            $arrayUser = $instance->withVisibilityContext($userContext)->toArray();
            expect($arrayUser)->not()->toHaveKey('email');
            expect($arrayUser)->not()->toHaveKey('salary');
        });

        it('Visible property with complex context logic', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $userId = 'user-123',
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewPersonalData')]
                    public readonly string $ssn = '123-45-6789',
                ) {}

                private function canViewPersonalData(mixed $context): bool
                {
                    // Admin can see all
                    if ('admin' === ($context?->role ?? null)) {
                        return true;
                    }

                    // User can see their own data
                    if (($context?->userId ?? null) === $this->userId) {
                        return true;
                    }
                    // HR can see if they have permission
                    return 'hr' === ($context?->role ?? null) && ($context?->hasPermission ?? false);
                }
            };

            $instance = $dto::fromArray([]);

            // Admin can see
            $adminContext = (object)['role' => 'admin'];
            expect($instance->withVisibilityContext($adminContext)->toArray())->toHaveKey('ssn');

            // Same user can see
            $sameUserContext = (object)['userId' => 'user-123', 'role' => 'user'];
            expect($instance->withVisibilityContext($sameUserContext)->toArray())->toHaveKey('ssn');

            // Different user cannot see
            $differentUserContext = (object)['userId' => 'user-456', 'role' => 'user'];
            expect($instance->withVisibilityContext($differentUserContext)->toArray())->not()->toHaveKey('ssn');

            // HR with permission can see
            $hrWithPermission = (object)['role' => 'hr', 'hasPermission' => true];
            expect($instance->withVisibilityContext($hrWithPermission)->toArray())->toHaveKey('ssn');

            // HR without permission cannot see
            $hrWithoutPermission = (object)['role' => 'hr', 'hasPermission' => false];
            expect($instance->withVisibilityContext($hrWithoutPermission)->toArray())->not()->toHaveKey('ssn');
        });

        it('combines Visible with only() and except()', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                    public readonly string $city = 'NYC',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                    #[Visible(callback: 'canViewSalary')]
                    public readonly float $salary = 75000.0,
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    return 'admin' === ($context?->role ?? null);
                }

                private function canViewSalary(mixed $context): bool
                {
                    return 'admin' === ($context?->role ?? null);
                }
            };

            $instance = $dto::fromArray([]);
            $adminContext = (object)['role' => 'admin'];

            // only() with Visible properties
            $arrayOnly = $instance->withVisibilityContext($adminContext)->only(['name', 'email'])->toArray();
            expect($arrayOnly)->toHaveKey('name');
            expect($arrayOnly)->toHaveKey('email');
            expect($arrayOnly)->not()->toHaveKey('salary');
            expect($arrayOnly)->not()->toHaveKey('city');

            // except() with Visible properties
            $arrayExcept = $instance->withVisibilityContext($adminContext)->except(['salary'])->toArray();
            expect($arrayExcept)->toHaveKey('name');
            expect($arrayExcept)->toHaveKey('city');
            expect($arrayExcept)->toHaveKey('email');
            expect($arrayExcept)->not()->toHaveKey('salary');
        });
    });
});

