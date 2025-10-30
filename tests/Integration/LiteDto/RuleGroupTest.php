<?php

declare(strict_types=1);

namespace Tests\Integration\LiteDto;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\RuleGroup;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Sometimes;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class RuleGroupTest_UserDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[RuleGroup(['create', 'update'])]
        public readonly string $name,

        #[Required]
        #[Email]
        #[RuleGroup(['create', 'update'])]
        public readonly string $email,

        #[Required]
        #[Min(8)]
        #[RuleGroup(['create'])]  // Only required when creating
        public readonly ?string $password = null,

        #[Sometimes]
        #[Min(8)]
        #[RuleGroup(['update'])]  // Only validated when updating
        public readonly ?string $newPassword = null,
    ) {}
}

class RuleGroupTest_PostDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[RuleGroup(['create', 'update', 'publish'])]
        public readonly string $title,

        #[Required]
        #[RuleGroup(['create', 'update', 'publish'])]
        public readonly string $content,

        #[Required]
        #[RuleGroup(['publish'])]  // Only required when publishing
        public readonly ?string $publishedAt = null,

        #[Sometimes]
        #[RuleGroup(['update'])]  // Only validated when updating
        public readonly ?string $updatedBy = null,
    ) {}
}

describe('RuleGroup Attribute', function(): void {
    describe('Basic RuleGroup Functionality', function(): void {
        it('validates only create group rules', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ];

            $dto = RuleGroupTest_UserDto::validateAndCreate($data, groups: ['create']);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->email)->toBe('john@example.com')
                ->and($dto->password)->toBe('secret123');
        });

        it('validates only update group rules', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'newPassword' => 'newsecret123',
            ];

            $dto = RuleGroupTest_UserDto::validateAndCreate($data, groups: ['update']);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->email)->toBe('john@example.com')
                ->and($dto->newPassword)->toBe('newsecret123');
        });

        it('fails validation when create group rules are not met', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // Missing password
            ];

            RuleGroupTest_UserDto::validateAndCreate($data, groups: ['create']);
        })->throws(ValidationException::class);

        it('passes validation when update group does not require password', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // No password required for update
            ];

            $dto = RuleGroupTest_UserDto::validateAndCreate($data, groups: ['update']);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->password)->toBeNull();
        });
    });

    describe('Multiple Groups', function(): void {
        it('validates rules from multiple groups', function(): void {
            $data = [
                'title' => 'My Post',
                'content' => 'Post content',
                'publishedAt' => '2024-01-01',
            ];

            $dto = RuleGroupTest_PostDto::validateAndCreate($data, groups: ['create', 'publish']);

            expect($dto->title)->toBe('My Post')
                ->and($dto->content)->toBe('Post content')
                ->and($dto->publishedAt)->toBe('2024-01-01');
        });

        it('validates only specified groups', function(): void {
            $data = [
                'title' => 'My Post',
                'content' => 'Post content',
                // publishedAt not required for create only
            ];

            $dto = RuleGroupTest_PostDto::validateAndCreate($data, groups: ['create']);

            expect($dto->title)->toBe('My Post')
                ->and($dto->publishedAt)->toBeNull();
        });

        it('fails when publish group rules are not met', function(): void {
            $data = [
                'title' => 'My Post',
                'content' => 'Post content',
                // Missing publishedAt
            ];

            RuleGroupTest_PostDto::validateAndCreate($data, groups: ['publish']);
        })->throws(ValidationException::class);
    });

    describe('No Groups Specified', function(): void {
        it('validates all rules when no groups specified', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ];

            // No groups = all rules apply
            $dto = RuleGroupTest_UserDto::validateAndCreate($data);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->password)->toBe('secret123');
        });

        it('fails when any required field is missing (no groups)', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // Missing password
            ];

            RuleGroupTest_UserDto::validateAndCreate($data);
        })->throws(ValidationException::class);
    });

    describe('Empty Groups Array', function(): void {
        it('validates all rules when empty groups array', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ];

            $dto = RuleGroupTest_UserDto::validateAndCreate($data, groups: []);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->password)->toBe('secret123');
        });
    });

    describe('Validation Result with Groups', function(): void {
        it('returns success for valid data with groups', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ];

            $result = RuleGroupTest_UserDto::validate($data, groups: ['update']);

            expect($result->isValid())->toBeTrue()
                ->and($result->errors())->toBe([]);
        });

        it('returns failure for invalid data with groups', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // Missing password for create group
            ];

            $result = RuleGroupTest_UserDto::validate($data, groups: ['create']);

            expect($result->isFailed())->toBeTrue()
                ->and($result->errors())->toHaveKey('password');
        });
    });
});
