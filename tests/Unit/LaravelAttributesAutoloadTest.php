<?php

declare(strict_types=1);

namespace Tests\Unit;

describe('Laravel Attributes Autoload Safety', function () {
    it('can load Laravel attributes without Laravel being installed', function () {
        // This test ensures that Laravel attributes can be loaded
        // even when Laravel is not installed (they just won't use facades)

        expect(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole'))->toBeTrue();
    });

    it('Laravel attributes work with context even without Laravel', function () {
        $dto = new class('John', 'john@example.com') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth]
                public readonly string $email,
            ) {}
        };

        // Should work with context
        $user = (object)['id' => 1];
        $array = $dto->withContext(['user' => $user])->toArray();

        expect($array)->toHaveKey('email');
    });

    it('does not throw errors when Laravel facades are not available', function () {
        $dto = new class('John', 'john@example.com') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth]
                public readonly string $email,
            ) {}
        };

        // Should not throw error even without Laravel
        expect(fn() => $dto->toArray())->not->toThrow(\Throwable::class);
    });

    it('Laravel attributes implement ConditionalProperty interface', function () {
        $whenAuth = new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth();
        $whenGuest = new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest();
        $whenCan = new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan('edit');
        $whenRole = new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole('admin');

        expect($whenAuth)->toBeInstanceOf(\event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty::class)
            ->and($whenGuest)->toBeInstanceOf(\event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty::class)
            ->and($whenCan)->toBeInstanceOf(\event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty::class)
            ->and($whenRole)->toBeInstanceOf(\event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty::class);
    });

    it('Laravel attributes can be instantiated without Laravel', function () {
        // Should not throw errors during instantiation
        expect(fn() => new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth())->not->toThrow(\Throwable::class)
            ->and(fn() => new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest())->not->toThrow(\Throwable::class)
            ->and(fn() => new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan('edit'))->not->toThrow(\Throwable::class)
            ->and(fn() => new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole('admin'))->not->toThrow(\Throwable::class);
    });

    it('Laravel attributes shouldInclude works without Laravel', function () {
        $whenAuth = new \event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth();
        $dto = new \stdClass();

        // Should not throw error
        expect(fn() => $whenAuth->shouldInclude('value', $dto, []))->not->toThrow(\Throwable::class);

        // Should return false without context
        expect($whenAuth->shouldInclude('value', $dto, []))->toBeFalse();

        // Should work with context
        expect($whenAuth->shouldInclude('value', $dto, ['user' => (object)['id' => 1]]))->toBeTrue();
    });

    it('all Laravel attributes work in plain PHP without Laravel', function () {
        $dto = new class('Test', 'email@test.com', '/admin', '/edit') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth]
                public readonly string $email,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole('admin')]
                public readonly string $adminPanel,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan('edit')]
                public readonly string $editLink,
            ) {}
        };

        // Without context - should work without errors
        $arrayNoContext = $dto->toArray();
        expect($arrayNoContext)->toHaveKey('name')
            ->and($arrayNoContext)->not->toHaveKey('email')
            ->and($arrayNoContext)->not->toHaveKey('adminPanel')
            ->and($arrayNoContext)->not->toHaveKey('editLink');

        // With context - should work correctly
        $admin = new class {
            public string $role = 'admin';
            public function can(string $ability): bool
            {
                return $ability === 'edit';
            }
        };

        $arrayWithContext = $dto->withContext(['user' => $admin])->toArray();
        expect($arrayWithContext)->toHaveKey('name')
            ->and($arrayWithContext)->toHaveKey('email')
            ->and($arrayWithContext)->toHaveKey('adminPanel')
            ->and($arrayWithContext)->toHaveKey('editLink');
    });

    it('WhenGuest defaults to true without Laravel', function () {
        $dto = new class('Page', 'Login here') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest]
                public readonly string $loginPrompt,
            ) {}
        };

        // Without context and without Laravel, should assume guest
        $array = $dto->toArray();
        expect($array)->toHaveKey('loginPrompt');
    });

    it('Laravel attributes do not cause autoload issues', function () {
        // This test ensures that using Laravel attributes doesn't
        // trigger autoload errors for Laravel classes

        // Create a DTO with all Laravel attributes
        $class = new class('Test') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth]
                public readonly string $auth = 'auth',

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest]
                public readonly string $guest = 'guest',

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole('admin')]
                public readonly string $role = 'role',

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan('edit')]
                public readonly string $can = 'can',
            ) {}
        };

        // Should not throw any errors
        expect(fn() => $class->toArray())->not->toThrow(\Throwable::class);
        expect(fn() => $class->jsonSerialize())->not->toThrow(\Throwable::class);
        expect(fn() => json_encode($class))->not->toThrow(\Throwable::class);
    });
})->group('laravel');

