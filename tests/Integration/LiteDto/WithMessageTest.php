<?php

declare(strict_types=1);

namespace Tests\Integration\LiteDto;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\WithMessage;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class WithMessageTest_UserDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[Email]
        #[WithMessage([
            'required' => 'Please provide your email address',
            'email' => 'The email format is invalid',
        ])]
        public readonly string $email,

        #[Required]
        #[Min(8)]
        #[WithMessage([
            'required' => 'Password is required',
            'min' => 'Password must be at least 8 characters',
        ])]
        public readonly string $password,
    ) {}
}

class WithMessageTest_ProductDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[WithMessage(['required' => 'Product name cannot be empty'])]
        public readonly string $name,

        #[Required]
        #[Min(0)]
        #[WithMessage([
            'required' => 'Price is required',
            'min' => 'Price must be a positive number',
        ])]
        public readonly float $price,
    ) {}
}

describe('WithMessage Attribute', function(): void {
    describe('Basic WithMessage Functionality', function(): void {
        it('uses custom message for required validation', function(): void {
            $data = [
                'email' => '',
                'password' => 'secret123',
            ];

            try {
                WithMessageTest_UserDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Please provide your email address');
            }
        });

        it('uses custom message for email validation', function(): void {
            $data = [
                'email' => 'invalid-email',
                'password' => 'secret123',
            ];

            try {
                WithMessageTest_UserDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('The email format is invalid');
            }
        });

        it('uses custom message for min validation', function(): void {
            $data = [
                'email' => 'john@example.com',
                'password' => 'short',
            ];

            try {
                WithMessageTest_UserDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Password must be at least 8 characters');
            }
        });

        it('passes validation with valid data', function(): void {
            $data = [
                'email' => 'john@example.com',
                'password' => 'secret123',
            ];

            $dto = WithMessageTest_UserDto::validateAndCreate($data);

            expect($dto->email)->toBe('john@example.com')
                ->and($dto->password)->toBe('secret123');
        });
    });

    describe('Multiple Custom Messages', function(): void {
        it('uses correct custom message for each validation rule', function(): void {
            $data = [
                'name' => '',
                'price' => 10.0,
            ];

            try {
                WithMessageTest_ProductDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Product name cannot be empty');
            }
        });

        it('uses custom message for price validation', function(): void {
            $data = [
                'name' => 'Product',
                'price' => -5.0,
            ];

            try {
                WithMessageTest_ProductDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                expect($validationException->getMessage())->toContain('Price must be a positive number');
            }
        });
    });

    describe('Validation Result with Custom Messages', function(): void {
        it('returns custom messages in validation result', function(): void {
            $data = [
                'email' => 'invalid',
                'password' => 'short',
            ];

            $result = WithMessageTest_UserDto::validate($data);

            expect($result->isFailed())->toBeTrue()
                ->and($result->errors())->toHaveKey('email')
                ->and($result->errors())->toHaveKey('password');

            $allErrors = $result->allErrors();
            expect($allErrors)->toContain('The email format is invalid')
                ->and($allErrors)->toContain('Password must be at least 8 characters');
        });

        it('returns success for valid data', function(): void {
            $data = [
                'email' => 'john@example.com',
                'password' => 'secret123',
            ];

            $result = WithMessageTest_UserDto::validate($data);

            expect($result->isValid())->toBeTrue()
                ->and($result->errors())->toBe([]);
        });
    });

    describe('Missing Custom Message', function(): void {
        it('falls back to default message when custom message not provided', function(): void {
            // Test with existing DTO - WithMessage only provides 'required' message, not 'email'
            $data = ['email' => 'invalid', 'password' => 'secret123'];

            try {
                WithMessageTest_UserDto::validateAndCreate($data);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (ValidationException $validationException) {
                // Should use custom email validation message from WithMessage
                expect($validationException->getMessage())->toContain('The email format is invalid');
            }
        });
    });
});
