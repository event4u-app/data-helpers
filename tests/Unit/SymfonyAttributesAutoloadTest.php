<?php

declare(strict_types=1);

namespace Tests\Unit;
use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;
use stdClass;
use Throwable;

describe('Symfony Attributes Autoload Safety', function(): void {
    it('can load Symfony attributes without Symfony being installed', function(): void {
        // This test ensures that Symfony attributes can be loaded
        // even when Symfony is not installed (they just won't use Security component)

        expect(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole'))->toBeTrue();
    });

    it('Symfony attributes work with context even without Symfony', function(): void {
        $dto = new class('My Post', '/edit') {
            use SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[WhenGranted('EDIT')]
                public readonly string $editLink,
            ) {}
        };

        // Should work with context
        $user = (object)['grants' => ['EDIT']];
        $array = $dto->withContext(['user' => $user])->toArray();

        expect($array)->toHaveKey('editLink');
    });

    it('does not throw errors when Symfony Security is not available', function(): void {
        $dto = new class('My Post', '/edit') {
            use SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[WhenGranted('EDIT')]
                public readonly string $editLink,
            ) {}
        };

        // Should not throw error even without Symfony
        expect(fn(): array => $dto->toArray())->not->toThrow(Throwable::class);
    });

    it('Symfony attributes implement ConditionalProperty interface', function(): void {
        $whenGranted = new WhenGranted('EDIT');
        $whenRole = new WhenRole('ROLE_ADMIN');

        expect($whenGranted)->toBeInstanceOf(ConditionalProperty::class)
            ->and($whenRole)->toBeInstanceOf(ConditionalProperty::class);
    });

    it('Symfony attributes can be instantiated without Symfony', function(): void {
        // Should not throw errors during instantiation
        expect(fn(): WhenGranted => new WhenGranted('EDIT'))->not->toThrow(
            Throwable::class
        )
            ->and(fn(): WhenRole => new WhenRole('ROLE_ADMIN'))->not->toThrow(
                Throwable::class
            );
    });

    it('Symfony attributes shouldInclude works without Symfony', function(): void {
        $whenGranted = new WhenGranted('EDIT');
        $dto = new stdClass();

        // Should not throw error
        expect(fn(): bool => $whenGranted->shouldInclude('value', $dto, []))->not->toThrow(Throwable::class);

        // Should return false without context
        expect($whenGranted->shouldInclude('value', $dto, []))->toBeFalse();

        // Should work with context
        $user = (object)['grants' => ['EDIT']];
        expect($whenGranted->shouldInclude('value', $dto, ['user' => $user]))->toBeTrue();
    });

    it('all Symfony attributes work in plain PHP without Symfony', function(): void {
        $dto = new class('Test', '/admin', '/edit') {
            use SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[WhenRole('ROLE_ADMIN')]
                public readonly string $adminPanel,

                #[WhenGranted('EDIT')]
                public readonly string $editLink,
            ) {}
        };

        // Without context - should work without errors
        $arrayNoContext = $dto->toArray();
        expect($arrayNoContext)->toHaveKey('name')
            ->and($arrayNoContext)->not->toHaveKey('adminPanel')
            ->and($arrayNoContext)->not->toHaveKey('editLink');

        // With context - should work correctly
        $admin = (object)[
            'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
            'grants' => ['EDIT', 'VIEW'],
        ];

        $arrayWithContext = $dto->withContext(['user' => $admin])->toArray();
        expect($arrayWithContext)->toHaveKey('name')
            ->and($arrayWithContext)->toHaveKey('adminPanel')
            ->and($arrayWithContext)->toHaveKey('editLink');
    });

    it('Symfony attributes do not cause autoload issues', function(): void {
        // This test ensures that using Symfony attributes doesn't
        // trigger autoload errors for Symfony classes

        // Create a DTO with all Symfony attributes
        $class = new class('Test') {
            use SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[WhenRole('ROLE_ADMIN')]
                public readonly string $role = 'role',

                #[WhenGranted('EDIT')]
                public readonly string $granted = 'granted',
            ) {}
        };

        // Should not throw any errors
        expect(fn(): array => $class->toArray())->not->toThrow(Throwable::class);
        expect(fn(): array => $class->jsonSerialize())->not->toThrow(Throwable::class);
        expect(fn() => json_encode($class))->not->toThrow(Throwable::class);
    });

    it('works with security context object', function(): void {
        $dto = new class('Test', '/admin') {
            use SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[WhenRole('ROLE_ADMIN')]
                public readonly string $adminPanel,
            ) {}
        };

        $security = new class {
            public function isGranted(string $attribute): bool
            {
                return 'ROLE_ADMIN' === $attribute;
            }
        };

        $array = $dto->withContext(['security' => $security])->toArray();
        expect($array)->toHaveKey('adminPanel');
    });

    it('WhenGranted works with subject parameter', function(): void {
        $dto = new class('Post', '/edit') {
            use SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[WhenGranted('EDIT', 'post')]
                public readonly string $editLink,
            ) {}
        };

        $post = (object)['id' => 1];
        $user = new class {
            public function isGranted(string $attribute, $subject = null): bool
            {
                return 'EDIT' === $attribute && null !== $subject;
            }
        };

        $array = $dto->withContext(['user' => $user, 'post' => $post])->toArray();
        expect($array)->toHaveKey('editLink');
    });
})->group('symfony');

