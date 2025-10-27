<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\SimpleDto\Attributes\Symfony\WhenRole;

// Test Dtos
class SymfonyCondDto1 extends SimpleDto { public function __construct(public readonly string $title, #[WhenGranted(
    'EDIT'
)] public readonly string $editLink) {} }
class SymfonyCondDto2 extends SimpleDto { public function __construct(public readonly string $title, #[WhenGranted(
    'EDIT',
    'post'
)] public readonly string $editLink) {} }
class SymfonyCondDto3 extends SimpleDto { public function __construct(public readonly string $title, #[WhenGranted(
    'EDIT',
    'post'
)] #[WhenGranted(
    'DELETE',
    'post'
)] public readonly string $adminLink) {} }
class SymfonyCondDto4 extends SimpleDto { public function __construct(public readonly string $name, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminPanel) {} }
class SymfonyCondDto5 extends SimpleDto { public function __construct(public readonly string $name, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminPanel, #[WhenRole(
    'ROLE_MODERATOR'
)] public readonly string $modPanel) {} }
class SymfonyCondDto6 extends SimpleDto { public function __construct(public readonly string $name, #[WhenRole([
    'ROLE_ADMIN',
    'ROLE_MODERATOR',
])] public readonly string $staffPanel) {} }
class SymfonyCondDto7 extends SimpleDto { public function __construct(public readonly string $title, #[WhenGranted(
    'EDIT'
)] #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminEditLink) {} }
class SymfonyCondDto8 extends SimpleDto { public function __construct(public readonly string $name, #[WhenRole([
    'ROLE_ADMIN',
    'ROLE_MODERATOR',
])] public readonly string $moderationPanel) {} }
class SymfonyCondDto9 extends SimpleDto { public function __construct(public readonly string $title, #[WhenGranted(
    'VIEW'
)] public readonly string $content) {} }
class SymfonyCondDto10 extends SimpleDto { public function __construct(public readonly string $name, #[WhenRole(
    'ROLE_ADMIN'
)] #[WhenGranted(
    'EDIT'
)] public readonly string $adminEditPanel) {} }
class SymfonyCondDto11 extends SimpleDto { public function __construct(public readonly string $title, #[WhenGranted(
    'EDIT'
)] public readonly string $editLink, #[WhenGranted(
    'DELETE'
)] public readonly string $deleteLink, #[WhenGranted(
    'PUBLISH'
)] public readonly string $publishLink, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminPanel) {} }
class SymfonyCondDto12 extends SimpleDto { public function __construct(public readonly string $title, #[WhenRole(
    'ROLE_USER'
)] public readonly string $userPanel, #[WhenRole(
    'ROLE_MODERATOR'
)] public readonly string $moderatorPanel, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminPanel) {} }
class SymfonyCondDto13 extends SimpleDto { public function __construct(public readonly string $title, #[WhenRole(
    'ROLE_ADMIN'
)] #[WhenGranted(
    'VIEW_SECRETS'
)] public readonly string $secretData) {} }
class SymfonyCondDto14 extends SimpleDto { public function __construct(public readonly string $name, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminPanel, #[WhenGranted(
    'EDIT'
)] public readonly string $editLink = '/edit') {} }
class SymfonyCondDto15 extends SimpleDto { public function __construct(public readonly string $content, #[WhenGranted(
    'EDIT'
)] public readonly string $editLink, #[WhenGranted(
    'DELETE'
)] public readonly string $deleteLink, #[WhenGranted(
    'PUBLISH'
)] public readonly string $publishLink, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminPanel) {} }
class SymfonyCondDto16 extends SimpleDto { public function __construct(public readonly string $content, #[WhenRole(
    'ROLE_USER'
)] public readonly string $userFeature, #[WhenRole([
    'ROLE_MODERATOR',
    'ROLE_ADMIN',
])] public readonly string $moderatorFeature, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminFeature) {} }
class SymfonyCondDto17 extends SimpleDto { public function __construct(public readonly string $content, #[WhenGranted(
    'EDIT'
)] public readonly string $editLink, #[WhenGranted(
    'DELETE'
)] public readonly string $deleteLink, #[WhenGranted(
    'PUBLISH'
)] public readonly string $publishLink, #[WhenRole(
    'ROLE_ADMIN'
)] public readonly string $adminLink) {} }

describe('Symfony Conditional Attributes', function(): void {
    describe('WhenGranted Attribute', function(): void {
        it('includes property when user is granted attribute (context)', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = (object)['grants' => ['EDIT', 'VIEW']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink')
                ->and($array['editLink'])->toBe('/edit');
        });

        it('excludes property when user is not granted attribute', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = (object)['grants' => ['VIEW']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('includes property when user has isGranted method', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = new class {
                public function isGranted(string $attribute, mixed $subject = null): bool
                {
                    return 'EDIT' === $attribute;
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('excludes property when user is guest', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('works with permissions array', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = (object)['permissions' => ['EDIT', 'DELETE']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('works with security context', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $security = new class {
                public function isGranted(string $attribute, mixed $subject = null): bool
                {
                    return 'EDIT' === $attribute;
                }
            };

            $array = $dto->withContext(['security' => $security])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('supports subject from context', function(): void {
            $dto = new SymfonyCondDto2('My Post', '/edit');

            $post = (object)['id' => 1, 'title' => 'My Post'];
            $user = new class {
                public function isGranted(string $attribute, mixed $subject = null): bool
                {
                    return 'EDIT' === $attribute && null !== $subject;
                }
            };

            $array = $dto->withContext(['user' => $user, 'post' => $post])->toArray();

            expect($array)->toHaveKey('editLink');
        });
    });

    describe('WhenRole Attribute', function(): void {
        it('includes property when user has role (single role)', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when user does not have role', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = (object)['roles' => ['ROLE_USER']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('includes property when user has one of multiple roles', function(): void {
            $dto = new SymfonyCondDto8('John', '/moderation');

            $userAdmin = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
            $userModerator = (object)['roles' => ['ROLE_MODERATOR', 'ROLE_USER']];
            $userRegular = (object)['roles' => ['ROLE_USER']];

            $arrayAdmin = $dto->withContext(['user' => $userAdmin])->toArray();
            $arrayModerator = $dto->withContext(['user' => $userModerator])->toArray();
            $arrayRegular = $dto->withContext(['user' => $userRegular])->toArray();

            expect($arrayAdmin)->toHaveKey('moderationPanel')
                ->and($arrayModerator)->toHaveKey('moderationPanel')
                ->and($arrayRegular)->not->toHaveKey('moderationPanel');
        });

        it('works with getRoles method (Symfony UserInterface)', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = new class {
                /** @return array<string> */
    public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('works with role property (string)', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = (object)['role' => 'ROLE_ADMIN'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('works with security context', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $security = new class {
                public function isGranted(string $role): bool
                {
                    return 'ROLE_ADMIN' === $role;
                }
            };

            $array = $dto->withContext(['security' => $security])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });
    });

    describe('Combined Attributes', function(): void {
        it('supports multiple Symfony attributes (AND logic)', function(): void {
            $dto = new SymfonyCondDto13('Secret Content', 'Top secret data');

            $adminWithPermission = new class {
                /** @var array<string> */
    public array $roles = ['ROLE_ADMIN', 'ROLE_USER'];
                /** @var array<string> */
    public array $grants = ['VIEW_SECRETS', 'EDIT'];
            };

            $adminWithoutPermission = new class {
                /** @var array<string> */
    public array $roles = ['ROLE_ADMIN', 'ROLE_USER'];
                /** @var array<string> */
    public array $grants = ['EDIT'];
            };

            $userWithPermission = new class {
                /** @var array<string> */
    public array $roles = ['ROLE_USER'];
                /** @var array<string> */
    public array $grants = ['VIEW_SECRETS'];
            };

            $arrayWith = $dto->withContext(['user' => $adminWithPermission])->toArray();
            $arrayAdminWithout = $dto->withContext(['user' => $adminWithoutPermission])->toArray();
            $arrayUserWith = $dto->withContext(['user' => $userWithPermission])->toArray();

            expect($arrayWith)->toHaveKey('secretData')
                ->and($arrayAdminWithout)->not->toHaveKey('secretData')
                ->and($arrayUserWith)->not->toHaveKey('secretData');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles empty roles array', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = (object)['roles' => []];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('handles missing roles property', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = (object)['name' => 'John'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('handles empty grants array', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = (object)['grants' => []];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('handles missing grants property', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = (object)['name' => 'John'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('handles null user in context', function(): void {
            $dto = new SymfonyCondDto14('John', '/admin');

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('adminPanel')
                ->and($array)->not->toHaveKey('editLink')
                ->and($array)->toHaveKey('name');
        });

        it('handles empty context', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $array = $dto->withContext([])->toArray();

            expect($array)->not->toHaveKey('adminPanel')
                ->and($array)->toHaveKey('name');
        });
    });

    describe('JSON Serialization', function(): void {
        it('works with json_encode and WhenRole', function(): void {
            $dto = new SymfonyCondDto4('John', '/admin');

            $user = (object)['roles' => ['ROLE_ADMIN']];
            $json = json_encode($dto->withContext(['user' => $user]));
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('adminPanel')
                ->and($decoded['adminPanel'])->toBe('/admin');
        });

        it('works with json_encode and WhenGranted', function(): void {
            $dto = new SymfonyCondDto1('My Post', '/edit');

            $user = (object)['grants' => ['EDIT']];
            $json = json_encode($dto->withContext(['user' => $user]));
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('editLink')
                ->and($decoded['editLink'])->toBe('/edit');
        });
    });

    describe('Complex Scenarios', function(): void {
        it('handles multiple properties with different conditions', function(): void {
            $dto = new SymfonyCondDto17('Content', '/edit', '/delete', '/publish', '/admin');

            $user = (object)[
                'roles' => ['ROLE_ADMIN'],
                'grants' => ['EDIT', 'DELETE'],
            ];

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink')
                ->and($array)->toHaveKey('deleteLink')
                ->and($array)->not->toHaveKey('publishLink')
                ->and($array)->toHaveKey('adminLink');
        });

        it('handles hierarchical roles', function(): void {
            $dto = new SymfonyCondDto16('Content', '/user', '/moderator', '/admin');

            $regularUser = (object)['roles' => ['ROLE_USER']];
            $moderator = (object)['roles' => ['ROLE_USER', 'ROLE_MODERATOR']];
            $admin = (object)['roles' => ['ROLE_USER', 'ROLE_ADMIN']];

            $arrayUser = $dto->withContext(['user' => $regularUser])->toArray();
            $arrayModerator = $dto->withContext(['user' => $moderator])->toArray();
            $arrayAdmin = $dto->withContext(['user' => $admin])->toArray();

            expect($arrayUser)->toHaveKey('userFeature')
                ->and($arrayUser)->not->toHaveKey('moderatorFeature')
                ->and($arrayUser)->not->toHaveKey('adminFeature')
                ->and($arrayModerator)->toHaveKey('userFeature')
                ->and($arrayModerator)->toHaveKey('moderatorFeature')
                ->and($arrayModerator)->not->toHaveKey('adminFeature')
                ->and($arrayAdmin)->toHaveKey('userFeature')
                ->and($arrayAdmin)->toHaveKey('moderatorFeature')
                ->and($arrayAdmin)->toHaveKey('adminFeature');
        });
    });
})->group('symfony');
