<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole;

// Test DTOs - WhenAuth
class LaravelCondDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly string $email,
    ) {}
}

// Test DTOs - WhenGuest
class LaravelCondDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenGuest]
        public readonly string $loginPrompt,
    ) {}
}

// Test DTOs - WhenCan
class LaravelCondDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenCan('edit-post')]
        public readonly string $editLink,
    ) {}
}

// Test DTOs - WhenRole (single)
class LaravelCondDTO4 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenRole('admin')]
        public readonly string $adminPanel,
    ) {}
}

// Test DTOs - WhenRole (multiple)
class LaravelCondDTO5 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenRole(['admin', 'moderator'])]
        public readonly string $moderationPanel,
    ) {}
}

// Test DTOs - Combined attributes
class LaravelCondDTO6 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenAuth]
        #[WhenRole('admin')]
        #[WhenCan('view-secrets')]
        public readonly string $secretData,
    ) {}
}

class LaravelCondDTO7 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenGuest]
        public readonly string $loginLink,
        #[WhenAuth]
        public readonly string $dashboardLink,
    ) {}
}

// Test DTOs - Edge cases
class LaravelCondDTO8 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenCan('edit-post')]
        public readonly string $editLink,
    ) {}
}

class LaravelCondDTO9 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenRole(['admin', 'moderator'])]
        public readonly string $adminPanel,
    ) {}
}

class LaravelCondDTO10 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[WhenCan('edit', 'post')]
        public readonly string $editLink,
    ) {}
}

describe('Laravel Conditional Attributes', function(): void {
    describe('WhenAuth Attribute', function(): void {
        it('includes property when user is authenticated (context)', function(): void {
            $dto = new LaravelCondDTO1('John', 'john@example.com');

            $user = (object)['id' => 1, 'name' => 'John'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('email')
                ->and($array['email'])->toBe('john@example.com');
        });

        it('excludes property when user is not authenticated (context)', function(): void {
            $dto = new LaravelCondDTO1('John', 'john@example.com');

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('email');
        });

        it('excludes property when no context provided', function(): void {
            $dto = new LaravelCondDTO1('John', 'john@example.com');

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('email');
        });
    });

    describe('WhenGuest Attribute', function(): void {
        it('includes property when user is guest (context)', function(): void {
            $dto = new LaravelCondDTO2('Home', 'Please log in');

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->toHaveKey('loginPrompt')
                ->and($array['loginPrompt'])->toBe('Please log in');
        });

        it('excludes property when user is authenticated (context)', function(): void {
            $dto = new LaravelCondDTO2('Home', 'Please log in');

            $user = (object)['id' => 1];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('loginPrompt');
        });

        it('includes property when no context provided (assumes guest)', function(): void {
            $dto = new LaravelCondDTO2('Home', 'Please log in');

            $array = $dto->toArray();

            expect($array)->toHaveKey('loginPrompt');
        });
    });

    describe('WhenCan Attribute', function(): void {
        it('includes property when user has ability (can method)', function(): void {
            $dto = new LaravelCondDTO3('My Post', '/edit');

            $user = new class {
                public function can(string $ability): bool
                {
                    return 'edit-post' === $ability;
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink')
                ->and($array['editLink'])->toBe('/edit');
        });

        it('excludes property when user does not have ability', function(): void {
            $dto = new LaravelCondDTO3('My Post', '/edit');

            $user = new class {
                public function can(string $ability): bool
                {
                    return false;
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('includes property when user has ability in abilities array', function(): void {
            $dto = new LaravelCondDTO3('My Post', '/edit');

            $user = (object)['abilities' => ['edit-post', 'delete-post']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('excludes property when user is guest', function(): void {
            $dto = new LaravelCondDTO3('My Post', '/edit');

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });
    });

    describe('WhenRole Attribute', function(): void {
        it('includes property when user has role (single role)', function(): void {
            $dto = new LaravelCondDTO4('John', '/admin');

            $user = (object)['role' => 'admin'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when user does not have role', function(): void {
            $dto = new LaravelCondDTO4('John', '/admin');

            $user = (object)['role' => 'user'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('includes property when user has one of multiple roles', function(): void {
            $dto = new LaravelCondDTO5('John', '/moderation');

            $userAdmin = (object)['role' => 'admin'];
            $userModerator = (object)['role' => 'moderator'];
            $userRegular = (object)['role' => 'user'];

            $arrayAdmin = $dto->withContext(['user' => $userAdmin])->toArray();
            $arrayModerator = $dto->withContext(['user' => $userModerator])->toArray();
            $arrayRegular = $dto->withContext(['user' => $userRegular])->toArray();

            expect($arrayAdmin)->toHaveKey('moderationPanel')
                ->and($arrayModerator)->toHaveKey('moderationPanel')
                ->and($arrayRegular)->not->toHaveKey('moderationPanel');
        });

        it('works with roles array property', function(): void {
            $dto = new LaravelCondDTO4('John', '/admin');

            $user = (object)['roles' => ['admin', 'editor']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('works with hasRole method', function(): void {
            $dto = new LaravelCondDTO4('John', '/admin');

            $user = new class {
                public function hasRole(string $role): bool
                {
                    return 'admin' === $role;
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });
    });

    describe('Combined Attributes', function(): void {
        it('supports multiple Laravel attributes (AND logic)', function(): void {
            $dto = new LaravelCondDTO6('Secret Content', 'Top secret data');

            $adminWithPermission = new class {
                public string $role = 'admin';
                public function can(string $ability): bool
                {
                    return 'view-secrets' === $ability;
                }
            };

            $adminWithoutPermission = new class {
                public string $role = 'admin';
                public function can(string $ability): bool
                {
                    return false;
                }
            };

            $arrayWith = $dto->withContext(['user' => $adminWithPermission])->toArray();
            $arrayWithout = $dto->withContext(['user' => $adminWithoutPermission])->toArray();
            $arrayGuest = $dto->withContext(['user' => null])->toArray();

            expect($arrayWith)->toHaveKey('secretData')
                ->and($arrayWithout)->not->toHaveKey('secretData')
                ->and($arrayGuest)->not->toHaveKey('secretData');
        });

        it('combines WhenAuth and WhenGuest correctly', function(): void {
            $dto = new LaravelCondDTO7('Page', 'Login', 'Dashboard');

            $arrayAuth = $dto->withContext(['user' => (object)['id' => 1]])->toArray();
            $arrayGuest = $dto->withContext(['user' => null])->toArray();

            expect($arrayAuth)->toHaveKey('dashboardLink')
                ->and($arrayAuth)->not->toHaveKey('loginLink')
                ->and($arrayGuest)->toHaveKey('loginLink')
                ->and($arrayGuest)->not->toHaveKey('dashboardLink');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles user with permissions array', function(): void {
            $dto = new LaravelCondDTO8('Post', '/edit');

            $user = (object)['permissions' => ['edit-post', 'delete-post']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('handles user with hasAnyRole method', function(): void {
            $dto = new LaravelCondDTO9('John', '/admin');

            $user = new class {
                /** @param array<string> $roles */
                public function hasAnyRole(array $roles): bool
                {
                    return in_array('admin', $roles, true);
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('handles WhenCan with model argument', function(): void {
            $dto = new LaravelCondDTO10('Post', '/edit');

            $post = (object)['id' => 1, 'title' => 'My Post'];
            $user = new class {
                public function can(string $ability, mixed $model = null): bool
                {
                    return 'edit' === $ability && null !== $model;
                }
            };

            $array = $dto->withContext(['user' => $user, 'post' => $post])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('handles empty roles array', function(): void {
            $dto = new LaravelCondDTO4('John', '/admin');

            $user = (object)['roles' => []];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });
    });
})->group('laravel');

