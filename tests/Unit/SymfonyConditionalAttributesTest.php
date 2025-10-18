<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole;

describe('Symfony Conditional Attributes', function () {
    describe('WhenGranted Attribute', function () {
        it('includes property when user is granted attribute (context)', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['grants' => ['EDIT', 'VIEW']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink')
                ->and($array['editLink'])->toBe('/edit');
        });

        it('excludes property when user is not granted attribute', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['grants' => ['VIEW']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('includes property when user has isGranted method', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = new class {
                public function isGranted(string $attribute, $subject = null): bool
                {
                    return $attribute === 'EDIT';
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('excludes property when user is guest', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('works with permissions array', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['permissions' => ['EDIT', 'DELETE']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('works with security context', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $security = new class {
                public function isGranted(string $attribute, $subject = null): bool
                {
                    return $attribute === 'EDIT';
                }
            };

            $array = $dto->withContext(['security' => $security])->toArray();

            expect($array)->toHaveKey('editLink');
        });

        it('supports subject from context', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT', 'post')]
                    public readonly string $editLink,
                ) {}
            };

            $post = (object)['id' => 1, 'title' => 'My Post'];
            $user = new class {
                public function isGranted(string $attribute, $subject = null): bool
                {
                    return $attribute === 'EDIT' && $subject !== null;
                }
            };

            $array = $dto->withContext(['user' => $user, 'post' => $post])->toArray();

            expect($array)->toHaveKey('editLink');
        });
    });

    describe('WhenRole Attribute', function () {
        it('includes property when user has role (single role)', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when user does not have role', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['roles' => ['ROLE_USER']];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('includes property when user has one of multiple roles', function () {
            $dto = new class('John', '/moderation') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
                    public readonly string $moderationPanel,
                ) {}
            };

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

        it('works with getRoles method (Symfony UserInterface)', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('works with role property (string)', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['role' => 'ROLE_ADMIN'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });

        it('works with security context', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $security = new class {
                public function isGranted(string $role): bool
                {
                    return $role === 'ROLE_ADMIN';
                }
            };

            $array = $dto->withContext(['security' => $security])->toArray();

            expect($array)->toHaveKey('adminPanel');
        });
    });

    describe('Combined Attributes', function () {
        it('supports multiple Symfony attributes (AND logic)', function () {
            $dto = new class('Secret Content', 'Top secret data') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenRole('ROLE_ADMIN')]
                    #[WhenGranted('VIEW_SECRETS')]
                    public readonly string $secretData,
                ) {}
            };

            $adminWithPermission = new class {
                public array $roles = ['ROLE_ADMIN', 'ROLE_USER'];
                public array $grants = ['VIEW_SECRETS', 'EDIT'];
            };

            $adminWithoutPermission = new class {
                public array $roles = ['ROLE_ADMIN', 'ROLE_USER'];
                public array $grants = ['EDIT'];
            };

            $userWithPermission = new class {
                public array $roles = ['ROLE_USER'];
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

    describe('Edge Cases', function () {
        it('handles empty roles array', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['roles' => []];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('handles missing roles property', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['name' => 'John'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('handles empty grants array', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['grants' => []];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('handles missing grants property', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['name' => 'John'];
            $array = $dto->withContext(['user' => $user])->toArray();

            expect($array)->not->toHaveKey('editLink');
        });

        it('handles null user in context', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink = '/edit',
                ) {}
            };

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('adminPanel')
                ->and($array)->not->toHaveKey('editLink')
                ->and($array)->toHaveKey('name');
        });

        it('handles empty context', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $array = $dto->withContext([])->toArray();

            expect($array)->not->toHaveKey('adminPanel')
                ->and($array)->toHaveKey('name');
        });
    });

    describe('JSON Serialization', function () {
        it('works with json_encode and WhenRole', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminPanel,
                ) {}
            };

            $user = (object)['roles' => ['ROLE_ADMIN']];
            $json = json_encode($dto->withContext(['user' => $user]));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('adminPanel')
                ->and($decoded['adminPanel'])->toBe('/admin');
        });

        it('works with json_encode and WhenGranted', function () {
            $dto = new class('My Post', '/edit') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $title,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,
                ) {}
            };

            $user = (object)['grants' => ['EDIT']];
            $json = json_encode($dto->withContext(['user' => $user]));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('editLink')
                ->and($decoded['editLink'])->toBe('/edit');
        });
    });

    describe('Complex Scenarios', function () {
        it('handles multiple properties with different conditions', function () {
            $dto = new class('Content', '/edit', '/delete', '/publish', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $content,

                    #[WhenGranted('EDIT')]
                    public readonly string $editLink,

                    #[WhenGranted('DELETE')]
                    public readonly string $deleteLink,

                    #[WhenGranted('PUBLISH')]
                    public readonly string $publishLink,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminLink,
                ) {}
            };

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

        it('handles hierarchical roles', function () {
            $dto = new class('Content', '/user', '/moderator', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $content,

                    #[WhenRole('ROLE_USER')]
                    public readonly string $userFeature,

                    #[WhenRole(['ROLE_MODERATOR', 'ROLE_ADMIN'])]
                    public readonly string $moderatorFeature,

                    #[WhenRole('ROLE_ADMIN')]
                    public readonly string $adminFeature,
                ) {}
            };

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
});

