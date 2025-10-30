<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\LiteDto\Attributes\Symfony\WhenRole;
use event4u\DataHelpers\LiteDto\Attributes\Symfony\WhenSymfonyRole;
use event4u\DataHelpers\LiteDto\LiteDto;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

// Test DTOs
class E2ESymfonyGrantedDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[WhenGranted('EDIT')]
        public readonly ?string $editLink = null,
    ) {}
}

class E2ESymfonyRoleDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole('ROLE_ADMIN')]
        public readonly ?string $adminPanel = null,
    ) {}
}

class E2ESymfonyMultiRoleDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
        public readonly ?string $moderationPanel = null,
    ) {}
}

class E2ESymfonySymfonyRoleDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenSymfonyRole('ROLE_ADMIN')]
        public readonly ?array $adminData = null,
    ) {}
}

describe('Symfony LiteDto Conditional Attributes E2E', function (): void {
    // Note: These E2E tests primarily test context-based conditional attributes
    // since setting up real Symfony Security in E2E tests requires complex configuration.
    // The Symfony-specific integration is tested in the integration tests.

    describe('WhenGranted', function (): void {

        it('includes property when user is granted (context)', function (): void {
            $user = new class {
                public function isGranted(string $attribute): bool
                {
                    return $attribute === 'EDIT';
                }
            };

            $dto = E2ESymfonyGrantedDto::from(['title' => 'My Post', 'editLink' => '/edit']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('editLink');
        });

        it('excludes property when user is not granted (context)', function (): void {
            $user = new class {
                public function isGranted(string $attribute): bool
                {
                    return false;
                }
            };

            $dto = E2ESymfonyGrantedDto::from(['title' => 'My Post', 'editLink' => '/edit']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->not->toHaveKey('editLink');
        });
    });

    describe('WhenRole', function (): void {
        it('includes property when user has role (context)', function (): void {
            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $dto = E2ESymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when user does not have role (context)', function (): void {
            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_USER'];
                }
            };

            $dto = E2ESymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('includes property when user has one of multiple roles (context)', function (): void {
            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_MODERATOR', 'ROLE_USER'];
                }
            };

            $dto = E2ESymfonyMultiRoleDto::from(['name' => 'John', 'moderationPanel' => '/moderation']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('moderationPanel');
        });

    });

    describe('WhenSymfonyRole', function (): void {
        it('includes property when user has role (context)', function (): void {
            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $dto = E2ESymfonySymfonyRoleDto::from(['name' => 'John', 'adminData' => ['key' => 'value']]);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('adminData')
                ->and($array['adminData'])->toBe(['key' => 'value']);
        });

        it('excludes property when user does not have role (context)', function (): void {
            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_USER'];
                }
            };

            $dto = E2ESymfonySymfonyRoleDto::from(['name' => 'John', 'adminData' => ['key' => 'value']]);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->not->toHaveKey('adminData');
        });
    });

    describe('toJson compatibility', function (): void {
        it('works with toJson', function (): void {
            $user = new class {
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $dto = E2ESymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $json = $dto->toJson(['user' => $user]);

            expect($json)->toContain('"adminPanel"')
                ->and($json)->toContain('/admin');
        });
    });
});

