<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole;

describe('Laravel Conditional Attributes', function () {
    describe('WhenAuth Attribute', function () {
        it('includes property when user is authenticated (context)', function () {
            $dto = new class('John', 'john@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenAuth]
                    public readonly string $email,
                ) {}
            };

            $user = (object)['id' => 1, 'name' => 'John'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('email')
                ->and($array['email'])->toBe('john@example.com');
        });

        it('excludes property when user is not authenticated (context)', function () {
            $dto = new class('John', 'john@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenAuth]
                    public readonly string $email,
                ) {}
            };

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('email');
        });

        it('excludes property when no context provided', function () {
            $dto = new class('John', 'john@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenAuth]
                    public readonly string $email,
                ) {}
            };

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('email');
        });
    });

    describe('WhenGuest Attribute', function () {
        it('includes property when user is guest (context)', function () {
            $dto = new class('Home', 'Please log in') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGuest]
                    public readonly string $loginPrompt,
                ) {}
            };

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->toHaveKey('loginPrompt')
                ->and($array['loginPrompt'])->toBe('Please log in');
        });

        it('excludes property when user is authenticated (context)', function () {
            $dto = new class('Home', 'Please log in') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGuest]
                    public readonly string $loginPrompt,
                ) {}
            };

            $user = (object)['id' => 1];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('loginPrompt');
        });

        it('includes property when no context provided (assumes guest)', function () {
            $dto = new class('Home', 'Please log in') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGuest]
                    public readonly string $loginPrompt,
                ) {}
            };

            $array = $dto->toArray();

            expect($array)->toHaveKey('loginPrompt');
        });
    });

    describe('WhenCan Attribute', function () {
        it('includes property when user has ability (can method)', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenCan('edit-post')]
                    public readonly string $editLink,
                ) {}
            };

            $user = new class {
                public function can(string $ability): bool
                {
                    return $ability === 'edit-post';
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink')
                ->and($array['editLink'])->toBe('/edit');
        });

        it('excludes property when user does not have ability', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenCan('edit-post')]
                    public readonly string $editLink,
                ) {}
            };

            $user = new class {
                public function can(string $ability): bool
                {
                    return false;
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('includes property when user has ability in abilities array', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenCan('edit-post')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['abilities' => ['edit-post', 'delete-post']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('excludes property when user is guest', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenCan('edit-post')]
                    public readonly string $editLink,
                ) {}
            };

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });
    });

    describe('WhenRole Attribute', function () {
        it('includes property when user has role (single role)', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['role' => 'admin'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when user does not have role', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['role' => 'user'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('includes property when user has one of multiple roles', function () {
            $dto = new class('John', '/moderation') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole(['admin', 'moderator'])]
                    public readonly string $moderationPanel,
                ) {}
            };

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

        it('works with roles array property', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['roles' => ['admin', 'editor']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('works with hasRole method', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = new class {
                public function hasRole(string $role): bool
                {
                    return $role === 'admin';
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });
    });

    describe('Combined Attributes', function () {
        it('supports multiple Laravel attributes (AND logic)', function () {
            $dto = new class('Secret Content', 'Top secret data') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenAuth]
                    #[WhenRole('admin')]
                    #[WhenCan('view-secrets')]
                    public readonly string $secretData,
                ) {}
            };

            $adminWithPermission = new class {
                public string $role = 'admin';
                public function can(string $ability): bool
                {
                    return $ability === 'view-secrets';
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

        it('combines WhenAuth and WhenGuest correctly', function () {
            $dto = new class('Page', 'Login', 'Dashboard') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGuest]
                    public readonly string $loginLink,

                    #[WhenAuth]
                    public readonly string $dashboardLink,
                ) {}
            };

            $arrayAuth = $dto->withContext(['user' => (object)['id' => 1]])->toArray();
            $arrayGuest = $dto->withContext(['user' => null])->toArray();

            expect($arrayAuth)->toHaveKey('dashboardLink')
                ->and($arrayAuth)->not->toHaveKey('loginLink')
                ->and($arrayGuest)->toHaveKey('loginLink')
                ->and($arrayGuest)->not->toHaveKey('dashboardLink');
        });
    });

    describe('Edge Cases', function () {
        it('handles user with permissions array', function () {
            $dto = new class('Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenCan('edit-post')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['permissions' => ['edit-post', 'delete-post']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('handles user with hasAnyRole method', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole(['admin', 'moderator'])]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = new class {
                public function hasAnyRole(array $roles): bool
                {
                    return in_array('admin', $roles, true);
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('handles WhenCan with model argument', function () {
            $dto = new class('Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenCan('edit', 'post')]
                    public readonly string $editLink,
                ) {}
            };

            $post = (object)['id' => 1, 'title' => 'My Post'];
            $user = new class {
                public function can(string $ability, $model = null): bool
                {
                    return $ability === 'edit' && $model !== null;
                }
            };

            $array = $dto->withContext(['user' => $user, 'post' => $post])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('handles empty roles array', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['roles' => []];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });
    });
});

