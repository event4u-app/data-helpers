<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;

describe('Visible Attribute - Comprehensive Callback Tests', function(): void {
    describe('Instance Method Callbacks', function(): void {
        it('works with private instance method', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewEmail(mixed $context): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'admin' === $context?->role;
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $userContext = (object)['role' => 'user'];

            $instance = $dto::fromArray([]);

            // Admin can see email
            $adminArray = $instance->withVisibilityContext($adminContext)->toArray();
            expect($adminArray)->toHaveKey('email');

            // User cannot see email
            $userArray = $instance->withVisibilityContext($userContext)->toArray();
            expect($userArray)->not()->toHaveKey('email');
        });

        it('works with protected instance method', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewSalary')]
                    public readonly float $salary = 50000.0,
                ) {}

                private function canViewSalary(mixed $context): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'admin' === $context?->role;
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($adminContext)->toArray();

            expect($array)->toHaveKey('salary');
        });

        it('works with public instance method', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewPhone')]
                    public readonly string $phone = '123-456-7890',
                ) {}

                public function canViewPhone(mixed $context): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'admin' === $context?->role;
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($adminContext)->toArray();

            expect($array)->toHaveKey('phone');
        });
    });

    describe('Static Method Callbacks', function(): void {
        it('works with static method on Dto class', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'static::canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                public static function canViewEmail(mixed $dto, mixed $context): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'admin' === $context?->role;
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $userContext = (object)['role' => 'user'];

            $instance = $dto::fromArray([]);

            // Admin can see email
            $adminArray = $instance->withVisibilityContext($adminContext)->toArray();
            expect($adminArray)->toHaveKey('email');

            // User cannot see email
            $userArray = $instance->withVisibilityContext($userContext)->toArray();
            expect($userArray)->not()->toHaveKey('email');
        });
    });

    describe('Error Handling', function(): void {
        it('hides property when callback method does not exist', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'nonExistentMethod')]
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($adminContext)->toArray();

            // Property should be hidden when callback fails
            expect($array)->not()->toHaveKey('email');
        });

        it('hides property when static method does not exist', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'NonExistentClass::method')]
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $adminContext = (object)['role' => 'admin'];
            $instance = $dto::fromArray([]);
            $array = $instance->withVisibilityContext($adminContext)->toArray();

            // Property should be hidden when callback fails
            expect($array)->not()->toHaveKey('email');
        });

        it('casts callback result to boolean', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'checkValue')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function checkValue(mixed $context): int
                {
                    /** @phpstan-ignore-next-line unknown */
                    return $context?->value ?? 0;
                }
            };

            $trueContext = (object)['value' => 1];
            $falseContext = (object)['value' => 0];

            $instance = $dto::fromArray([]);

            // Non-zero value should be cast to true
            $trueArray = $instance->withVisibilityContext($trueContext)->toArray();
            expect($trueArray)->toHaveKey('email');

            // Zero value should be cast to false
            $falseArray = $instance->withVisibilityContext($falseContext)->toArray();
            expect($falseArray)->not()->toHaveKey('email');
        });
    });

    describe('Complex Scenarios', function(): void {
        it('works with multiple Visible properties using different callback types', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'John',
                    #[Visible(callback: 'canViewPhone')]
                    public readonly string $phone = '123-456-7890',
                    #[Visible(callback: 'static::canViewEmail')]
                    public readonly string $email = 'john@example.com',
                ) {}

                private function canViewPhone(mixed $context): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    /** @phpstan-ignore-next-line unknown */
                    return 'admin' === $context?->role || 'manager' === $context?->role;
                }

                public static function canViewEmail(mixed $dto, mixed $context): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 'admin' === $context?->role;
                }
            };

            $adminContext = (object)['role' => 'admin'];
            $managerContext = (object)['role' => 'manager'];
            $userContext = (object)['role' => 'user'];

            $instance = $dto::fromArray([]);

            // Admin sees everything
            $adminArray = $instance->withVisibilityContext($adminContext)->toArray();
            expect($adminArray)->toHaveKey('name');
            expect($adminArray)->toHaveKey('phone');
            expect($adminArray)->toHaveKey('email');

            // Manager sees phone but not email
            $managerArray = $instance->withVisibilityContext($managerContext)->toArray();
            expect($managerArray)->toHaveKey('name');
            expect($managerArray)->toHaveKey('phone');
            expect($managerArray)->not()->toHaveKey('email');

            // User sees nothing
            $userArray = $instance->withVisibilityContext($userContext)->toArray();
            expect($userArray)->toHaveKey('name');
            expect($userArray)->not()->toHaveKey('phone');
            expect($userArray)->not()->toHaveKey('email');
        });
    });
});
