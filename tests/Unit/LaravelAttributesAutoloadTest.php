<?php

declare(strict_types=1);

namespace Tests\Unit;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;
use stdClass;
use Throwable;

// Test DTOs
class LaravelAutoloadTestDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly string $email,
    ) {}
}

class LaravelAutoloadTestDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly string $email,
    ) {}
}

class LaravelAutoloadTestDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly string $email,
        #[WhenRole('admin')]
        public readonly string $adminPanel,
        #[WhenCan('edit')]
        public readonly string $editLink,
    ) {}
}

class LaravelAutoloadTestDTO4 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenGuest]
        public readonly string $loginPrompt,
    ) {}
}

class LaravelAutoloadTestDTO5 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly string $auth = 'auth',
        #[WhenGuest]
        public readonly string $guest = 'guest',
        #[WhenRole('admin')]
        public readonly string $role = 'role',
        #[WhenCan('edit')]
        public readonly string $can = 'can',
    ) {}
}

describe('Laravel Attributes Autoload Safety', function(): void {
    it('can load Laravel attributes without Laravel being installed', function(): void {
        // This test ensures that Laravel attributes can be loaded
        // even when Laravel is not installed (they just won't use facades)

        expect(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole'))->toBeTrue();
    });

    it('Laravel attributes work with context even without Laravel', function(): void {
        $dto = new LaravelAutoloadTestDTO1('John', 'john@example.com');

        // Should work with context
        $user = (object)['id' => 1];
        $array = $dto->withContext(['user' => $user])->toArray();

        expect($array)->toHaveKey('email');
    });

    it('does not throw errors when Laravel facades are not available', function(): void {
        $dto = new LaravelAutoloadTestDTO2('John', 'john@example.com');

        // Should not throw error even without Laravel
        expect(fn(): array => $dto->toArray())->not->toThrow(Throwable::class);
    });

    it('Laravel attributes implement ConditionalProperty interface', function(): void {
        $whenAuth = new WhenAuth();
        $whenGuest = new WhenGuest();
        $whenCan = new WhenCan('edit');
        $whenRole = new WhenRole('admin');

        expect($whenAuth)->toBeInstanceOf(ConditionalProperty::class)
            ->and($whenGuest)->toBeInstanceOf(ConditionalProperty::class)
            ->and($whenCan)->toBeInstanceOf(ConditionalProperty::class)
            ->and($whenRole)->toBeInstanceOf(ConditionalProperty::class);
    });

    it('Laravel attributes can be instantiated without Laravel', function(): void {
        // Should not throw errors during instantiation
        expect(fn(): WhenAuth => new WhenAuth())->not->toThrow(
            Throwable::class
        )
            ->and(fn(): WhenGuest => new WhenGuest())->not->toThrow(
                Throwable::class
            )
            ->and(fn(): WhenCan => new WhenCan('edit'))->not->toThrow(
                Throwable::class
            )
            ->and(fn(): WhenRole => new WhenRole('admin'))->not->toThrow(
                Throwable::class
            );
    });

    it('Laravel attributes shouldInclude works without Laravel', function(): void {
        $whenAuth = new WhenAuth();
        $dto = new stdClass();

        // Should not throw error
        expect(fn(): bool => $whenAuth->shouldInclude('value', $dto, []))->not->toThrow(Throwable::class);

        // Should return false without context
        expect($whenAuth->shouldInclude('value', $dto, []))->toBeFalse();

        // Should work with context
        expect($whenAuth->shouldInclude('value', $dto, ['user' => (object)['id' => 1]]))->toBeTrue();
    });

    it('all Laravel attributes work in plain PHP without Laravel', function(): void {
        $dto = new LaravelAutoloadTestDTO3('Test', 'email@test.com', '/admin', '/edit');

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
                return 'edit' === $ability;
            }
        };

        $arrayWithContext = $dto->withContext(['user' => $admin])->toArray();
        expect($arrayWithContext)->toHaveKey('name')
            ->and($arrayWithContext)->toHaveKey('email')
            ->and($arrayWithContext)->toHaveKey('adminPanel')
            ->and($arrayWithContext)->toHaveKey('editLink');
    });

    it('WhenGuest defaults to true without Laravel', function(): void {
        $dto = new LaravelAutoloadTestDTO4('Page', 'Login here');

        // Without context and without Laravel, should assume guest
        $array = $dto->toArray();
        expect($array)->toHaveKey('loginPrompt');
    });

    it('Laravel attributes do not cause autoload issues', function(): void {
        // This test ensures that using Laravel attributes doesn't
        // trigger autoload errors for Laravel classes

        // Create a DTO with all Laravel attributes
        $class = new LaravelAutoloadTestDTO5('Test');

        // Should not throw any errors
        expect(fn(): array => $class->toArray())->not->toThrow(Throwable::class);
        expect(fn(): array => $class->jsonSerialize())->not->toThrow(Throwable::class);
        expect(fn() => json_encode($class))->not->toThrow(Throwable::class);
    });
})->group('laravel');
