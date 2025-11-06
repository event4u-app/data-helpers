<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto\Attributes\WhenGranted;
use event4u\DataHelpers\SimpleDto\Attributes\WhenInstanceOf;
use event4u\DataHelpers\SimpleDto\Attributes\WhenSymfonyRole;
use event4u\DataHelpers\SimpleDto\SimpleDto;

// Test DTOs
class E2ESimpleDtoGrantedDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,
        #[WhenGranted('EDIT')]
        public readonly ?string $editLink = null,
    ) {}
}

class E2ESimpleDtoGrantedWithSubjectDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,
        #[WhenGranted('EDIT', 'post')]
        public readonly ?string $editLink = null,
    ) {}
}

class E2ESimpleDtoSymfonyRoleDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenSymfonyRole('ROLE_ADMIN')]
        public readonly ?string $adminPanel = null,
    ) {}
}

class E2ESimpleDtoMultiRoleDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenSymfonyRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
        public readonly ?string $moderationPanel = null,
    ) {}
}

class E2ESimpleDtoInstanceOfDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenInstanceOf(DateTime::class)]
        public readonly mixed $createdAt = null,
    ) {}
}

class E2ESimpleDtoInstanceOfCustomDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenInstanceOf(E2ESimpleDtoGrantedDto::class)]
        public readonly mixed $relatedDto = null,
    ) {}
}

describe('Symfony SimpleDto Conditional Attributes E2E', function (): void {
    // Note: These E2E tests primarily test context-based conditional attributes
    // since setting up real Symfony Security in E2E tests requires complex configuration.
    // The Symfony-specific integration is tested in the integration tests.

    describe('WhenGranted', function (): void {

        it('includes property when user is granted (context)', function (): void {
            $user = new class {
                public function isGranted(string $attribute, mixed $subject = null): bool
                {
                    return $attribute === 'EDIT';
                }
            };

            $dto = E2ESimpleDtoGrantedDto::from(['title' => 'My Post', 'editLink' => '/edit']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('editLink')
                ->and($array['editLink'])->toBe('/edit');
        });

        it('excludes property when user is not granted (context)', function (): void {
            $user = new class {
                public function isGranted(string $attribute, mixed $subject = null): bool
                {
                    return false;
                }
            };

            $dto = E2ESimpleDtoGrantedDto::from(['title' => 'My Post', 'editLink' => '/edit']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->not->toHaveKey('editLink')
                ->and($array)->toHaveKey('title');
        });

        it('includes property when user is granted with subject (context)', function (): void {
            $user = new class {
                public function isGranted(string $attribute, mixed $subject = null): bool
                {
                    return $attribute === 'EDIT' && $subject === 'post';
                }
            };

            $dto = E2ESimpleDtoGrantedWithSubjectDto::from(['title' => 'My Post', 'editLink' => '/edit']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('editLink');
        });

        it('checks ROLE_ prefix with getRoles method', function (): void {
            $user = new class {
                /** @return array<string> */
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $dto = new class('Test', '/admin') extends SimpleDto {
                public function __construct(
                    public readonly string $title,
                    #[WhenGranted('ROLE_ADMIN')]
                    public readonly ?string $adminLink = null,
                ) {}
            };

            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('adminLink');
        });
    });

    describe('WhenSymfonyRole', function (): void {
        it('includes property when user has role (context)', function (): void {
            $user = new class {
                /** @return array<string> */
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $dto = E2ESimpleDtoSymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when user does not have role (context)', function (): void {
            $user = new class {
                /** @return array<string> */
                public function getRoles(): array
                {
                    return ['ROLE_USER'];
                }
            };

            $dto = E2ESimpleDtoSymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->not->toHaveKey('adminPanel')
                ->and($array)->toHaveKey('name');
        });

        it('includes property when user has one of multiple roles (context)', function (): void {
            $user = new class {
                /** @return array<string> */
                public function getRoles(): array
                {
                    return ['ROLE_MODERATOR', 'ROLE_USER'];
                }
            };

            $dto = E2ESimpleDtoMultiRoleDto::from(['name' => 'John', 'moderationPanel' => '/moderation']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('moderationPanel')
                ->and($array['moderationPanel'])->toBe('/moderation');
        });

        it('excludes property when user has none of the required roles', function (): void {
            $user = new class {
                /** @return array<string> */
                public function getRoles(): array
                {
                    return ['ROLE_USER'];
                }
            };

            $dto = E2ESimpleDtoMultiRoleDto::from(['name' => 'John', 'moderationPanel' => '/moderation']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->not->toHaveKey('moderationPanel');
        });

        it('returns false when no user context provided', function (): void {
            $dto = E2ESimpleDtoSymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $array = $dto->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });
    });

    describe('WhenInstanceOf', function (): void {
        it('includes property when value is instance of class', function (): void {
            $date = new DateTime('2024-01-01');
            $dto = E2ESimpleDtoInstanceOfDto::from(['name' => 'John', 'createdAt' => $date]);
            $array = $dto->toArray();

            expect($array)->toHaveKey('createdAt')
                ->and($array['createdAt'])->toBeInstanceOf(DateTime::class);
        });

        it('excludes property when value is not instance of class', function (): void {
            $dto = E2ESimpleDtoInstanceOfDto::from(['name' => 'John', 'createdAt' => '2024-01-01']);
            $array = $dto->toArray();

            expect($array)->not->toHaveKey('createdAt')
                ->and($array)->toHaveKey('name');
        });

        it('excludes property when value is null', function (): void {
            $dto = E2ESimpleDtoInstanceOfDto::from(['name' => 'John', 'createdAt' => null]);
            $array = $dto->toArray();

            expect($array)->not->toHaveKey('createdAt');
        });

        it('works with custom DTO classes', function (): void {
            $relatedDto = E2ESimpleDtoGrantedDto::from(['title' => 'Related', 'editLink' => '/edit']);
            $dto = E2ESimpleDtoInstanceOfCustomDto::from(['name' => 'John', 'relatedDto' => $relatedDto]);
            $array = $dto->toArray();

            expect($array)->toHaveKey('relatedDto')
                ->and($array['relatedDto'])->toBeArray()
                ->and($array['relatedDto'])->toHaveKey('title')
                ->and($array['relatedDto']['title'])->toBe('Related');
        });

        it('excludes property when value is different DTO class', function (): void {
            $wrongDto = E2ESimpleDtoSymfonyRoleDto::from(['name' => 'Jane', 'adminPanel' => '/admin']);
            $dto = E2ESimpleDtoInstanceOfCustomDto::from(['name' => 'John', 'relatedDto' => $wrongDto]);
            $array = $dto->toArray();

            expect($array)->not->toHaveKey('relatedDto');
        });
    });

    describe('toJson compatibility', function (): void {
        it('works with toJson for WhenSymfonyRole using withContext', function (): void {
            $user = new class {
                /** @return array<string> */
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN', 'ROLE_USER'];
                }
            };

            $dto = E2ESimpleDtoSymfonyRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $json = $dto->withContext(['user' => $user])->toJson();

            expect($json)->toContain('"adminPanel"')
                ->and($json)->toContain('/admin');
        });

        it('works with toJson for WhenInstanceOf', function (): void {
            $date = new DateTime('2024-01-01');
            $dto = E2ESimpleDtoInstanceOfDto::from(['name' => 'John', 'createdAt' => $date]);
            $json = $dto->toJson();

            expect($json)->toContain('"createdAt"')
                ->and($json)->toContain('2024-01-01');
        });
    });
});

