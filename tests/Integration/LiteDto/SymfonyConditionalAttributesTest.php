<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\LiteDto\Attributes\Symfony\WhenRole;
use event4u\DataHelpers\LiteDto\Attributes\Symfony\WhenSymfonyRole;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class SymfonyConditionalAttributesTest_GrantedLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[WhenGranted('EDIT')]
        public readonly ?string $editLink = null,
    ) {}
}

class SymfonyConditionalAttributesTest_RoleLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole('ROLE_ADMIN')]
        public readonly ?string $adminPanel = null,
    ) {}
}

class SymfonyConditionalAttributesTest_MultiRoleLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
        public readonly ?string $moderationPanel = null,
    ) {}
}

class SymfonyConditionalAttributesTest_SymfonyRoleLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenSymfonyRole('ROLE_ADMIN')]
        public readonly ?array $adminData = null,
    ) {}
}

// Tests for WhenGranted
test('WhenGranted includes property when user is granted (context)', function(): void {
    $user = (object)['grants' => ['EDIT', 'VIEW']];
    $dto = SymfonyConditionalAttributesTest_GrantedLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('editLink')
        ->and($array['editLink'])->toBe('/edit');
});

test('WhenGranted excludes property when user is not granted (context)', function(): void {
    $user = (object)['grants' => ['VIEW']];
    $dto = SymfonyConditionalAttributesTest_GrantedLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('editLink');
});

test('WhenGranted excludes property when user is null (context)', function(): void {
    $dto = SymfonyConditionalAttributesTest_GrantedLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->not->toHaveKey('editLink');
});

test('WhenGranted works with user isGranted method', function(): void {
    $user = new class {
        public function isGranted(string $attribute): bool
        {
            return 'EDIT' === $attribute;
        }
    };
    $dto = SymfonyConditionalAttributesTest_GrantedLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('editLink');
});

test('WhenGranted works with security context', function(): void {
    $security = new class {
        public function isGranted(string $attribute): bool
        {
            return 'EDIT' === $attribute;
        }
    };
    $dto = SymfonyConditionalAttributesTest_GrantedLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['security' => $security]);

    expect($array)->toHaveKey('editLink');
});

// Tests for WhenRole (Symfony)
test('WhenRole includes property when user has role (context)', function(): void {
    $user = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
    $dto = SymfonyConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('adminPanel')
        ->and($array['adminPanel'])->toBe('/admin');
});

test('WhenRole excludes property when user does not have role (context)', function(): void {
    $user = (object)['roles' => ['ROLE_USER']];
    $dto = SymfonyConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('adminPanel');
});

test('WhenRole excludes property when user is null (context)', function(): void {
    $dto = SymfonyConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->not->toHaveKey('adminPanel');
});

test('WhenRole with multiple roles includes property when user has one of the roles', function(): void {
    $user = (object)['roles' => ['ROLE_MODERATOR', 'ROLE_USER']];
    $dto = SymfonyConditionalAttributesTest_MultiRoleLiteDto::from([
        'name' => 'John', 'moderationPanel' => '/moderation']
    );
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('moderationPanel');
});

test('WhenRole with multiple roles excludes property when user has none of the roles', function(): void {
    $user = (object)['roles' => ['ROLE_USER']];
    $dto = SymfonyConditionalAttributesTest_MultiRoleLiteDto::from([
        'name' => 'John', 'moderationPanel' => '/moderation']
    );
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('moderationPanel');
});

test('WhenRole works with user getRoles method', function(): void {
    $user = new class {
        public function getRoles(): array
        {
            return ['ROLE_ADMIN', 'ROLE_USER'];
        }
    };
    $dto = SymfonyConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('adminPanel');
});

test('WhenRole works with security context', function(): void {
    $security = new class {
        public function isGranted(string $role): bool
        {
            return 'ROLE_ADMIN' === $role;
        }
    };
    $dto = SymfonyConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['security' => $security]);

    expect($array)->toHaveKey('adminPanel');
});

// Tests for WhenSymfonyRole
test('WhenSymfonyRole includes property when user has role (context)', function(): void {
    $user = new class {
        public function getRoles(): array
        {
            return ['ROLE_ADMIN', 'ROLE_USER'];
        }
    };
    $dto = SymfonyConditionalAttributesTest_SymfonyRoleLiteDto::from([
        'name' => 'John',
        'adminData' => ['key' => 'value'],
    ]);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('adminData');
});

test('WhenSymfonyRole excludes property when user does not have role (context)', function(): void {
    $user = new class {
        public function getRoles(): array
        {
            return ['ROLE_USER'];
        }
    };
    $dto = SymfonyConditionalAttributesTest_SymfonyRoleLiteDto::from([
        'name' => 'John',
        'adminData' => ['key' => 'value'],
    ]);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('adminData');
});

test('WhenSymfonyRole works with security context', function(): void {
    $security = new class {
        public function isGranted(string $role): bool
        {
            return 'ROLE_ADMIN' === $role;
        }
    };
    $dto = SymfonyConditionalAttributesTest_SymfonyRoleLiteDto::from([
        'name' => 'John',
        'adminData' => ['key' => 'value'],
    ]);
    $array = $dto->toArray(['security' => $security]);

    expect($array)->toHaveKey('adminData');
});

// Test toJson
test('Symfony conditional attributes work with toJson', function(): void {
    $user = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
    $dto = SymfonyConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $json = $dto->toJson(['user' => $user]);

    expect($json)->toContain('"adminPanel"');
});
