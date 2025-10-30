<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Validation\Confirmed;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ConfirmedBy;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Different;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Same;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Size;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class ComparisonValidationTestSizeStringDto extends LiteDto
{
    public function __construct(
        #[Size(10)]
        public readonly string $phoneNumber,
    ) {}
}

class ComparisonValidationTestSizeArrayDto extends LiteDto
{
    public function __construct(
        #[Size(5)]
        public readonly array $tags,
    ) {}
}

class ComparisonValidationTestSizeNumberDto extends LiteDto
{
    public function __construct(
        #[Size(100)]
        public readonly int $value,
    ) {}
}

class ComparisonValidationTestConfirmedDto extends LiteDto
{
    public function __construct(
        #[Confirmed]
        public readonly string $password,
        public readonly string $password_confirmation,
    ) {}
}

class ComparisonValidationTestConfirmedCustomDto extends LiteDto
{
    public function __construct(
        #[Confirmed(field: 'customConfirmation')]
        public readonly string $password,
        public readonly string $customConfirmation,
    ) {}
}

class ComparisonValidationTestConfirmedByDto extends LiteDto
{
    public function __construct(
        #[ConfirmedBy('passwordVerification')]
        public readonly string $password,
        public readonly string $passwordVerification,
    ) {}
}

class ComparisonValidationTestSameDto extends LiteDto
{
    public function __construct(
        public readonly string $password,
        #[Same('password')]
        public readonly string $passwordConfirmation,
    ) {}
}

class ComparisonValidationTestDifferentDto extends LiteDto
{
    public function __construct(
        public readonly string $email,
        #[Different('email')]
        public readonly string $alternativeEmail,
    ) {}
}

class ComparisonValidationTestMultipleDto extends LiteDto
{
    public function __construct(
        #[Size(8)]
        public readonly string $password,
        #[Same('password')]
        public readonly string $passwordConfirmation,
        public readonly string $email,
        #[Different('email')]
        public readonly string $alternativeEmail,
    ) {}
}

describe('LiteDto Comparison Validation Attributes', function(): void {
    describe('Size Attribute', function(): void {
        it('validates string with exact size', function(): void {
            $dto = ComparisonValidationTestSizeStringDto::from(['phoneNumber' => '1234567890']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for string with wrong size', function(): void {
            $dto = ComparisonValidationTestSizeStringDto::from(['phoneNumber' => '123']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('phoneNumber'))->toBeTrue();
        });

        it('validates array with exact size', function(): void {
            $dto = ComparisonValidationTestSizeArrayDto::from(['tags' => ['a', 'b', 'c', 'd', 'e']]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for array with wrong size', function(): void {
            $dto = ComparisonValidationTestSizeArrayDto::from(['tags' => ['a', 'b', 'c']]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('tags'))->toBeTrue();
        });

        it('validates number with exact value', function(): void {
            $dto = ComparisonValidationTestSizeNumberDto::from(['value' => 100]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for number with wrong value', function(): void {
            $dto = ComparisonValidationTestSizeNumberDto::from(['value' => 50]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('value'))->toBeTrue();
        });
    });

    describe('Confirmed Attribute', function(): void {
        it('validates when password and confirmation match', function(): void {
            $result = ComparisonValidationTestConfirmedDto::validate([
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when password and confirmation do not match', function(): void {
            $result = ComparisonValidationTestConfirmedDto::validate([
                'password' => 'secret123',
                'password_confirmation' => 'different',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });

        it('fails when confirmation field is missing', function(): void {
            $result = ComparisonValidationTestConfirmedDto::validate([
                'password' => 'secret123',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });

        it('validates with custom confirmation field', function(): void {
            $result = ComparisonValidationTestConfirmedCustomDto::validate([
                'password' => 'secret123',
                'customConfirmation' => 'secret123',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails with custom confirmation field mismatch', function(): void {
            $result = ComparisonValidationTestConfirmedCustomDto::validate([
                'password' => 'secret123',
                'customConfirmation' => 'different',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });
    });

    describe('ConfirmedBy Attribute', function(): void {
        it('validates when password and verification match', function(): void {
            $result = ComparisonValidationTestConfirmedByDto::validate([
                'password' => 'secret123',
                'passwordVerification' => 'secret123',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when password and verification do not match', function(): void {
            $result = ComparisonValidationTestConfirmedByDto::validate([
                'password' => 'secret123',
                'passwordVerification' => 'different',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });

        it('fails when verification field is missing', function(): void {
            $result = ComparisonValidationTestConfirmedByDto::validate([
                'password' => 'secret123',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });
    });

    describe('Same Attribute', function(): void {
        it('validates when fields match', function(): void {
            $result = ComparisonValidationTestSameDto::validate([
                'password' => 'secret123',
                'passwordConfirmation' => 'secret123',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when fields do not match', function(): void {
            $result = ComparisonValidationTestSameDto::validate([
                'password' => 'secret123',
                'passwordConfirmation' => 'different',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('passwordConfirmation'))->toBeTrue();
        });

        it('fails when comparison field is missing', function(): void {
            $result = ComparisonValidationTestSameDto::validate([
                'passwordConfirmation' => 'secret123',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('passwordConfirmation'))->toBeTrue();
        });
    });

    describe('Different Attribute', function(): void {
        it('validates when fields are different', function(): void {
            $result = ComparisonValidationTestDifferentDto::validate([
                'email' => 'user@example.com',
                'alternativeEmail' => 'other@example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when fields are the same', function(): void {
            $result = ComparisonValidationTestDifferentDto::validate([
                'email' => 'user@example.com',
                'alternativeEmail' => 'user@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('alternativeEmail'))->toBeTrue();
        });

        it('validates when comparison field is missing', function(): void {
            $result = ComparisonValidationTestDifferentDto::validate([
                'alternativeEmail' => 'other@example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Multiple Comparison Attributes', function(): void {
        it('validates when all conditions are met', function(): void {
            $result = ComparisonValidationTestMultipleDto::validate([
                'password' => 'secret12',
                'passwordConfirmation' => 'secret12',
                'email' => 'user@example.com',
                'alternativeEmail' => 'other@example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when Size condition is not met', function(): void {
            $result = ComparisonValidationTestMultipleDto::validate([
                'password' => 'short',
                'passwordConfirmation' => 'short',
                'email' => 'user@example.com',
                'alternativeEmail' => 'other@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });

        it('fails when Same condition is not met', function(): void {
            $result = ComparisonValidationTestMultipleDto::validate([
                'password' => 'secret12',
                'passwordConfirmation' => 'different',
                'email' => 'user@example.com',
                'alternativeEmail' => 'other@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('passwordConfirmation'))->toBeTrue();
        });

        it('fails when Different condition is not met', function(): void {
            $result = ComparisonValidationTestMultipleDto::validate([
                'password' => 'secret12',
                'passwordConfirmation' => 'secret12',
                'email' => 'user@example.com',
                'alternativeEmail' => 'user@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('alternativeEmail'))->toBeTrue();
        });

        it('fails when multiple conditions are not met', function(): void {
            $result = ComparisonValidationTestMultipleDto::validate([
                'password' => 'short',
                'passwordConfirmation' => 'different',
                'email' => 'user@example.com',
                'alternativeEmail' => 'user@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
            expect($result->hasError('passwordConfirmation'))->toBeTrue();
            expect($result->hasError('alternativeEmail'))->toBeTrue();
        });
    });

    describe('validateInstance() method', function(): void {
        it('validates existing DTO instance', function(): void {
            $dto = ComparisonValidationTestSameDto::from([
                'password' => 'secret123',
                'passwordConfirmation' => 'secret123',
            ]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('returns errors for invalid DTO instance', function(): void {
            $dto = ComparisonValidationTestSameDto::from([
                'password' => 'secret123',
                'passwordConfirmation' => 'different',
            ]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('passwordConfirmation'))->toBeTrue();
        });
    });
});
