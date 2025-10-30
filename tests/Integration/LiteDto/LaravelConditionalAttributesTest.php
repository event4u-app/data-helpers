<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenAuth;
use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenCan;
use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenGuest;
use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenRole;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class LaravelConditionalAttributesTest_AuthLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}

class LaravelConditionalAttributesTest_GuestLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[WhenGuest]
        public readonly ?string $loginPrompt = null,
    ) {}
}

class LaravelConditionalAttributesTest_RoleLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole('admin')]
        public readonly ?string $adminPanel = null,
    ) {}
}

class LaravelConditionalAttributesTest_MultiRoleLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole(['admin', 'moderator'])]
        public readonly ?string $moderationPanel = null,
    ) {}
}

class LaravelConditionalAttributesTest_CanLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[WhenCan('edit-post')]
        public readonly ?string $editLink = null,
    ) {}
}

// Tests for WhenAuth
test('WhenAuth includes property when user is authenticated (context)', function(): void {
    $user = (object)['id' => 1, 'name' => 'John'];
    $dto = LaravelConditionalAttributesTest_AuthLiteDto::from(['name' => 'John', 'email' => 'john@example.com']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('email')
        ->and($array['email'])->toBe('john@example.com');
});

test('WhenAuth excludes property when user is not authenticated (context)', function(): void {
    $dto = LaravelConditionalAttributesTest_AuthLiteDto::from(['name' => 'John', 'email' => 'john@example.com']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->not->toHaveKey('email');
});

test('WhenAuth excludes property when no context provided', function(): void {
    $dto = LaravelConditionalAttributesTest_AuthLiteDto::from(['name' => 'John', 'email' => 'john@example.com']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('email');
});

// Tests for WhenGuest
test('WhenGuest includes property when user is guest (context)', function(): void {
    $dto = LaravelConditionalAttributesTest_GuestLiteDto::from(['title' => 'Home', 'loginPrompt' => 'Please log in']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->toHaveKey('loginPrompt')
        ->and($array['loginPrompt'])->toBe('Please log in');
});

test('WhenGuest excludes property when user is authenticated (context)', function(): void {
    $user = (object)['id' => 1, 'name' => 'John'];
    $dto = LaravelConditionalAttributesTest_GuestLiteDto::from(['title' => 'Home', 'loginPrompt' => 'Please log in']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('loginPrompt');
});

test('WhenGuest includes property when no context provided', function(): void {
    $dto = LaravelConditionalAttributesTest_GuestLiteDto::from(['title' => 'Home', 'loginPrompt' => 'Please log in']);
    $array = $dto->toArray();

    expect($array)->toHaveKey('loginPrompt');
});

// Tests for WhenRole
test('WhenRole includes property when user has role (context)', function(): void {
    $user = (object)['role' => 'admin'];
    $dto = LaravelConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('adminPanel')
        ->and($array['adminPanel'])->toBe('/admin');
});

test('WhenRole excludes property when user does not have role (context)', function(): void {
    $user = (object)['role' => 'user'];
    $dto = LaravelConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('adminPanel');
});

test('WhenRole excludes property when user is null (context)', function(): void {
    $dto = LaravelConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->not->toHaveKey('adminPanel');
});

test('WhenRole with multiple roles includes property when user has one of the roles', function(): void {
    $user = (object)['role' => 'moderator'];
    $dto = LaravelConditionalAttributesTest_MultiRoleLiteDto::from([
        'name' => 'John', 'moderationPanel' => '/moderation']
    );
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('moderationPanel');
});

test('WhenRole with multiple roles excludes property when user has none of the roles', function(): void {
    $user = (object)['role' => 'user'];
    $dto = LaravelConditionalAttributesTest_MultiRoleLiteDto::from([
        'name' => 'John', 'moderationPanel' => '/moderation']
    );
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('moderationPanel');
});

test('WhenRole works with user roles array', function(): void {
    $user = (object)['roles' => ['user', 'admin']];
    $dto = LaravelConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('adminPanel');
});

// Tests for WhenCan
test('WhenCan includes property when user can perform ability (context)', function(): void {
    $user = new class {
        public function can(string $ability): bool
        {
            return 'edit-post' === $ability;
        }
    };
    $dto = LaravelConditionalAttributesTest_CanLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('editLink')
        ->and($array['editLink'])->toBe('/edit');
});

test('WhenCan excludes property when user cannot perform ability (context)', function(): void {
    $user = new class {
        public function can(string $ability): bool
        {
            return false;
        }
    };
    $dto = LaravelConditionalAttributesTest_CanLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->not->toHaveKey('editLink');
});

test('WhenCan excludes property when user is null (context)', function(): void {
    $dto = LaravelConditionalAttributesTest_CanLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->not->toHaveKey('editLink');
});

test('WhenCan works with user abilities array', function(): void {
    $user = (object)['abilities' => ['edit-post', 'delete-post']];
    $dto = LaravelConditionalAttributesTest_CanLiteDto::from(['title' => 'My Post', 'editLink' => '/edit']);
    $array = $dto->toArray(['user' => $user]);

    expect($array)->toHaveKey('editLink');
});

// Test toJson
test('Laravel conditional attributes work with toJson', function(): void {
    $user = (object)['role' => 'admin'];
    $dto = LaravelConditionalAttributesTest_RoleLiteDto::from(['name' => 'John', 'adminPanel' => '/admin']);
    $json = $dto->toJson(['user' => $user]);

    expect($json)->toContain('"adminPanel"');
});
