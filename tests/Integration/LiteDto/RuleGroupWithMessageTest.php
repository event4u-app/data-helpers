<?php

declare(strict_types=1);

namespace Tests\Integration\LiteDto;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\RuleGroup;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Sometimes;
use event4u\DataHelpers\LiteDto\Attributes\WithMessage;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class RuleGroupWithMessageTest_UserDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[RuleGroup(['create', 'update'])]
        #[WithMessage(['required' => 'Name is required for all operations'])]
        public readonly string $name,

        #[Required]
        #[Email]
        #[RuleGroup(['create', 'update'])]
        #[WithMessage([
            'required' => 'Email is required',
            'email' => 'Please provide a valid email address',
        ])]
        public readonly string $email,

        #[Required]
        #[Min(8)]
        #[RuleGroup(['create'])]
        #[WithMessage([
            'required' => 'Password is required when creating account',
            'min' => 'Password must be at least 8 characters long',
        ])]
        public readonly ?string $password = null,

        #[Sometimes]
        #[Min(8)]
        #[RuleGroup(['update'])]
        #[WithMessage(['min' => 'New password must be at least 8 characters long'])]
        public readonly ?string $newPassword = null,
    ) {}
}

describe('RuleGroup with WithMessage', function(): void {
    describe('Create Group with Custom Messages', function(): void {
        it('uses custom message for create group validation failure', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // Missing password
            ];

            try {
                RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['create']);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Password is required when creating account');
            }
        });

        it('uses custom message for password min validation in create group', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'short',
            ];

            try {
                RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['create']);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Password must be at least 8 characters long');
            }
        });

        it('passes validation with valid create data', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ];

            $dto = RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['create']);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->email)->toBe('john@example.com')
                ->and($dto->password)->toBe('secret123');
        });
    });

    describe('Update Group with Custom Messages', function(): void {
        it('does not require password for update group', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // No password required for update
            ];

            $dto = RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['update']);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->password)->toBeNull();
        });

        it('uses custom message for newPassword validation in update group', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'newPassword' => 'short',
            ];

            try {
                RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['update']);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain(
                    'New password must be at least 8 characters long'
                );
            }
        });

        it('uses custom message for email validation in update group', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'invalid-email',
            ];

            try {
                RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['update']);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Please provide a valid email address');
            }
        });
    });

    describe('Multiple Groups with Custom Messages', function(): void {
        it('validates both create and update groups with custom messages', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
                'newPassword' => 'newsecret123',
            ];

            $dto = RuleGroupWithMessageTest_UserDto::validateAndCreate($data, groups: ['create', 'update']);

            expect($dto->name)->toBe('John Doe')
                ->and($dto->password)->toBe('secret123')
                ->and($dto->newPassword)->toBe('newsecret123');
        });
    });

    describe('No Groups with Custom Messages', function(): void {
        it('validates all rules with custom messages when no groups specified', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                // Missing password
            ];

            try {
                RuleGroupWithMessageTest_UserDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Password is required when creating account');
            }
        });
    });

    describe('Validation Result with Groups and Custom Messages', function(): void {
        it('returns custom messages in validation result for specific group', function(): void {
            $data = [
                'name' => 'John Doe',
                'email' => 'invalid',
                'password' => 'short',
            ];

            $result = RuleGroupWithMessageTest_UserDto::validate($data, groups: ['create']);

            expect($result->isFailed())->toBeTrue();

            $allErrors = $result->allErrors();
            expect($allErrors)->toContain('Please provide a valid email address')
                ->and($allErrors)->toContain('Password must be at least 8 characters long');
        });
    });
});
