<?php

declare(strict_types=1);

namespace Tests\Unit;

describe('Symfony Attributes Autoload Safety', function () {
    it('can load Symfony attributes without Symfony being installed', function () {
        // This test ensures that Symfony attributes can be loaded
        // even when Symfony is not installed (they just won't use Security component)

        expect(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted'))->toBeTrue()
            ->and(class_exists('event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole'))->toBeTrue();
    });

    it('Symfony attributes work with context even without Symfony', function () {
        $dto = new class('My Post', '/edit') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT')]
                public readonly string $editLink,
            ) {}
        };

        // Should work with context
        $user = (object)['grants' => ['EDIT']];
        $array = $dto->withContext(['user' => $user])->toArray();

        expect($array)->toHaveKey('editLink');
    });

    it('does not throw errors when Symfony Security is not available', function () {
        $dto = new class('My Post', '/edit') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT')]
                public readonly string $editLink,
            ) {}
        };

        // Should not throw error even without Symfony
        expect(fn() => $dto->toArray())->not->toThrow(\Throwable::class);
    });

    it('Symfony attributes implement ConditionalProperty interface', function () {
        $whenGranted = new \event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT');
        $whenRole = new \event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole('ROLE_ADMIN');

        expect($whenGranted)->toBeInstanceOf(\event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty::class)
            ->and($whenRole)->toBeInstanceOf(\event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty::class);
    });

    it('Symfony attributes can be instantiated without Symfony', function () {
        // Should not throw errors during instantiation
        expect(fn() => new \event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT'))->not->toThrow(\Throwable::class)
            ->and(fn() => new \event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole('ROLE_ADMIN'))->not->toThrow(\Throwable::class);
    });

    it('Symfony attributes shouldInclude works without Symfony', function () {
        $whenGranted = new \event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT');
        $dto = new \stdClass();

        // Should not throw error
        expect(fn() => $whenGranted->shouldInclude('value', $dto, []))->not->toThrow(\Throwable::class);

        // Should return false without context
        expect($whenGranted->shouldInclude('value', $dto, []))->toBeFalse();

        // Should work with context
        $user = (object)['grants' => ['EDIT']];
        expect($whenGranted->shouldInclude('value', $dto, ['user' => $user]))->toBeTrue();
    });

    it('all Symfony attributes work in plain PHP without Symfony', function () {
        $dto = new class('Test', '/admin', '/edit') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole('ROLE_ADMIN')]
                public readonly string $adminPanel,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT')]
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

    it('Symfony attributes do not cause autoload issues', function () {
        // This test ensures that using Symfony attributes doesn't
        // trigger autoload errors for Symfony classes

        // Create a DTO with all Symfony attributes
        $class = new class('Test') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole('ROLE_ADMIN')]
                public readonly string $role = 'role',

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT')]
                public readonly string $granted = 'granted',
            ) {}
        };

        // Should not throw any errors
        expect(fn() => $class->toArray())->not->toThrow(\Throwable::class);
        expect(fn() => $class->jsonSerialize())->not->toThrow(\Throwable::class);
        expect(fn() => json_encode($class))->not->toThrow(\Throwable::class);
    });

    it('works with security context object', function () {
        $dto = new class('Test', '/admin') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $name,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole('ROLE_ADMIN')]
                public readonly string $adminPanel,
            ) {}
        };

        $security = new class {
            public function isGranted(string $attribute): bool
            {
                return $attribute === 'ROLE_ADMIN';
            }
        };

        $array = $dto->withContext(['security' => $security])->toArray();
        expect($array)->toHaveKey('adminPanel');
    });

    it('WhenGranted works with subject parameter', function () {
        $dto = new class('Post', '/edit') {
            use \event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;

            public function __construct(
                public readonly string $title,

                #[\event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted('EDIT', 'post')]
                public readonly string $editLink,
            ) {}
        };

        $post = (object)['id' => 1];
        $user = new class {
            public function isGranted(string $attribute, $subject = null): bool
            {
                return $attribute === 'EDIT' && $subject !== null;
            }
        };

        $array = $dto->withContext(['user' => $user, 'post' => $post])->toArray();
        expect($array)->toHaveKey('editLink');
    });
})->group('symfony');

