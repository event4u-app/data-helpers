<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenAuth;
use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenCan;
use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenGuest;
use event4u\DataHelpers\LiteDto\Attributes\Laravel\WhenRole;
use event4u\DataHelpers\LiteDto\LiteDto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

// Test DTOs
class E2ELaravelAuthDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}

class E2ELaravelGuestDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[WhenGuest]
        public readonly ?string $loginPrompt = null,
    ) {}
}

class E2ELaravelRoleDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenRole('admin')]
        public readonly ?string $adminPanel = null,
    ) {}
}

class E2ELaravelCanDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[WhenCan('edit-post')]
        public readonly ?string $editLink = null,
    ) {}
}

describe('Laravel LiteDto Conditional Attributes E2E', function (): void {
    // Note: These E2E tests primarily test context-based conditional attributes
    // since setting up real Laravel Auth/Gate in E2E tests requires complex setup.
    // The Laravel-specific facade integration is tested in the integration tests.

    describe('Context-based (without Laravel facades)', function (): void {
        it('WhenAuth works with context', function (): void {
            $user = (object)['id' => 1, 'name' => 'John'];
            $dto = E2ELaravelAuthDto::from(['name' => 'John', 'email' => 'john@example.com']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('email');
        });

        it('WhenGuest works with context', function (): void {
            $dto = E2ELaravelGuestDto::from(['title' => 'Home', 'loginPrompt' => 'Please log in']);
            $array = $dto->toArray(['user' => null]);

            expect($array)->toHaveKey('loginPrompt');
        });

        it('WhenRole works with context', function (): void {
            $user = (object)['role' => 'admin'];
            $dto = E2ELaravelRoleDto::from(['name' => 'John', 'adminPanel' => '/admin']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('adminPanel');
        });

        it('WhenCan works with context', function (): void {
            $user = new class {
                public function can(string $ability): bool
                {
                    return $ability === 'edit-post';
                }
            };
            $dto = E2ELaravelCanDto::from(['title' => 'My Post', 'editLink' => '/edit']);
            $array = $dto->toArray(['user' => $user]);

            expect($array)->toHaveKey('editLink');
        });
    });
});

