<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Nullable;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Sometimes;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Url;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class MetaValidationTestNullableDto extends LiteDto
{
    public function __construct(
        #[Nullable]
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

class MetaValidationTestNullableWithRequiredDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Nullable]
        #[Url]
        public readonly ?string $website = null,
    ) {}
}

class MetaValidationTestSometimesDto extends LiteDto
{
    public function __construct(
        #[Sometimes]
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

class MetaValidationTestSometimesWithMinDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Sometimes]
        #[Min(8)]
        public readonly ?string $password = null,
    ) {}
}

class MetaValidationTestCombinedDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Sometimes]
        #[Email]
        public readonly ?string $email = null,
        #[Nullable]
        #[Url]
        public readonly ?string $website = null,
    ) {}
}

describe('LiteDto Meta Validation Attributes', function(): void {
    describe('Nullable Attribute', function(): void {
        it('allows null value when Nullable is present', function(): void {
            $result = MetaValidationTestNullableDto::validate(['email' => null]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates non-null value normally', function(): void {
            $result = MetaValidationTestNullableDto::validate(['email' => 'user@example.com']);
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid non-null value', function(): void {
            $result = MetaValidationTestNullableDto::validate(['email' => 'invalid-email']);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('works with other validation rules', function(): void {
            $result = MetaValidationTestNullableWithRequiredDto::validate([
                'name' => 'John',
                'website' => null,
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates non-null URL correctly', function(): void {
            $result = MetaValidationTestNullableWithRequiredDto::validate([
                'name' => 'John',
                'website' => 'https://example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid URL even with Nullable', function(): void {
            $result = MetaValidationTestNullableWithRequiredDto::validate([
                'name' => 'John',
                'website' => 'not-a-url',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('website'))->toBeTrue();
        });
    });

    describe('Sometimes Attribute', function(): void {
        it('skips validation when field is not present', function(): void {
            $result = MetaValidationTestSometimesDto::validate([]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when field is present and valid', function(): void {
            $result = MetaValidationTestSometimesDto::validate(['email' => 'user@example.com']);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when field is present but invalid', function(): void {
            $result = MetaValidationTestSometimesDto::validate(['email' => 'invalid-email']);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('validates null value when field is present', function(): void {
            $result = MetaValidationTestSometimesDto::validate(['email' => null]);
            expect($result->isValid())->toBeTrue();
        });

        it('works with Min validation', function(): void {
            $result = MetaValidationTestSometimesWithMinDto::validate(['name' => 'John']);
            expect($result->isValid())->toBeTrue();
        });

        it('validates password when present and valid', function(): void {
            $result = MetaValidationTestSometimesWithMinDto::validate([
                'name' => 'John',
                'password' => 'secret123',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when password is present but too short', function(): void {
            $result = MetaValidationTestSometimesWithMinDto::validate([
                'name' => 'John',
                'password' => 'short',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('password'))->toBeTrue();
        });
    });

    describe('Combined Meta Attributes', function(): void {
        it('validates when all conditions are met', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'name' => 'John',
                'email' => 'john@example.com',
                'website' => 'https://example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when Sometimes field is missing', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'name' => 'John',
                'website' => 'https://example.com',
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when Nullable field is null', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'name' => 'John',
                'email' => 'john@example.com',
                'website' => null,
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('validates when both optional fields are missing/null', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'name' => 'John',
                'website' => null,
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when Required field is missing', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'email' => 'john@example.com',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('name'))->toBeTrue();
        });

        it('fails when Sometimes field is present but invalid', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'name' => 'John',
                'email' => 'invalid-email',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('fails when Nullable field is present but invalid', function(): void {
            $result = MetaValidationTestCombinedDto::validate([
                'name' => 'John',
                'website' => 'not-a-url',
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('website'))->toBeTrue();
        });
    });

    describe('validateInstance() with Meta Attributes', function(): void {
        it('validates instance with Nullable null value', function(): void {
            $dto = MetaValidationTestNullableDto::from(['email' => null]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates instance with Nullable valid value', function(): void {
            $dto = MetaValidationTestNullableDto::from(['email' => 'user@example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails instance validation with Nullable invalid value', function(): void {
            $dto = MetaValidationTestNullableDto::from(['email' => 'invalid-email']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });

        it('validates instance with Sometimes - all properties present', function(): void {
            $dto = MetaValidationTestSometimesDto::from(['email' => 'user@example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates instance with Sometimes - null value', function(): void {
            $dto = MetaValidationTestSometimesDto::from(['email' => null]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('validateAndCreate() with Meta Attributes', function(): void {
        it('creates DTO when Nullable validation passes', function(): void {
            $dto = MetaValidationTestNullableDto::validateAndCreate(['email' => null]);
            expect($dto->email)->toBeNull();
        });

        it('creates DTO when Sometimes field is missing', function(): void {
            $dto = MetaValidationTestSometimesDto::validateAndCreate([]);
            expect($dto->email)->toBeNull();
        });

        it('throws exception when Sometimes field is present but invalid', function(): void {
            expect(
                fn(): \MetaValidationTestSometimesDto => MetaValidationTestSometimesDto::validateAndCreate([
                    'email' => 'invalid']
                )
            )
                ->toThrow(ValidationException::class);
        });
    });
});
