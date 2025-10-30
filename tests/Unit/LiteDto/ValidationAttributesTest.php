<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Between;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Max;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs for ValidationAttributesTest
class ValidationAttributesTestRequiredDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
    ) {}
}

class ValidationAttributesTestEmailDto extends LiteDto
{
    public function __construct(
        #[Email]
        public readonly string $email,
    ) {}
}

class ValidationAttributesTestMinStringDto extends LiteDto
{
    public function __construct(
        #[Min(3)]
        public readonly string $name,
    ) {}
}

class ValidationAttributesTestMinIntDto extends LiteDto
{
    public function __construct(
        #[Min(18)]
        public readonly int $age,
    ) {}
}

class ValidationAttributesTestMaxStringDto extends LiteDto
{
    public function __construct(
        #[Max(10)]
        public readonly string $name,
    ) {}
}

class ValidationAttributesTestMaxIntDto extends LiteDto
{
    public function __construct(
        #[Max(120)]
        public readonly int $age,
    ) {}
}

class ValidationAttributesTestBetweenDto extends LiteDto
{
    public function __construct(
        #[Between(18, 120)]
        public readonly int $age,
    ) {}
}

class ValidationAttributesTestMultipleDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
    ) {}
}

class ValidationAttributesTestComplexDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required]
        #[Email]
        public readonly string $email,
    ) {}
}

describe('LiteDto Validation Attributes', function(): void {
    describe('Required Attribute', function(): void {
        it('validates required fields', function(): void {
            $dto = ValidationAttributesTestRequiredDto::from(['name' => 'John']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when required field is missing', function(): void {
            $dto = ValidationAttributesTestRequiredDto::from(['name' => '']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('name'))->toBeTrue();
        });
    });

    describe('Email Attribute', function(): void {
        it('validates email addresses', function(): void {
            $dto = ValidationAttributesTestEmailDto::from(['email' => 'john@example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid email', function(): void {
            $dto = ValidationAttributesTestEmailDto::from(['email' => 'not-an-email']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });
    });

    describe('Min Attribute', function(): void {
        it('validates minimum string length', function(): void {
            $dto = ValidationAttributesTestMinStringDto::from(['name' => 'John']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when string is too short', function(): void {
            $dto = ValidationAttributesTestMinStringDto::from(['name' => 'Jo']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('name'))->toBeTrue();
        });

        it('validates minimum numeric value', function(): void {
            $dto = ValidationAttributesTestMinIntDto::from(['age' => 18]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when number is too small', function(): void {
            $dto = ValidationAttributesTestMinIntDto::from(['age' => 17]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('age'))->toBeTrue();
        });
    });

    describe('Max Attribute', function(): void {
        it('validates maximum string length', function(): void {
            $dto = ValidationAttributesTestMaxStringDto::from(['name' => 'John']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when string is too long', function(): void {
            $dto = ValidationAttributesTestMaxStringDto::from(['name' => 'VeryLongName']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('name'))->toBeTrue();
        });

        it('validates maximum numeric value', function(): void {
            $dto = ValidationAttributesTestMaxIntDto::from(['age' => 100]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when number is too large', function(): void {
            $dto = ValidationAttributesTestMaxIntDto::from(['age' => 121]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('age'))->toBeTrue();
        });
    });

    describe('Between Attribute', function(): void {
        it('validates value between min and max', function(): void {
            $dto = ValidationAttributesTestBetweenDto::from(['age' => 25]);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when value is below min', function(): void {
            $dto = ValidationAttributesTestBetweenDto::from(['age' => 17]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('age'))->toBeTrue();
        });

        it('fails when value is above max', function(): void {
            $dto = ValidationAttributesTestBetweenDto::from(['age' => 121]);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('age'))->toBeTrue();
        });
    });

    describe('Multiple Validation Attributes', function(): void {
        it('validates multiple attributes on same property', function(): void {
            $dto = ValidationAttributesTestMultipleDto::from(['email' => 'john@example.com']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails when any validation fails', function(): void {
            $dto = ValidationAttributesTestMultipleDto::from(['email' => '']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });
    });

    describe('validate() static method', function(): void {
        it('validates data before creating DTO', function(): void {
            $result = ValidationAttributesTestComplexDto::validate(['name' => 'John', 'email' => 'john@example.com']);
            expect($result->isValid())->toBeTrue();
        });

        it('returns errors for invalid data', function(): void {
            $result = ValidationAttributesTestComplexDto::validate(['name' => '', 'email' => 'invalid']);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('name'))->toBeTrue();
            expect($result->hasError('email'))->toBeTrue();
        });
    });

    describe('validateAndCreate() static method', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = ValidationAttributesTestRequiredDto::validateAndCreate(['name' => 'John']);
            expect($dto->name)->toBe('John');
        });

        it('throws exception when validation fails', function(): void {
            expect(
                fn(): \ValidationAttributesTestRequiredDto => ValidationAttributesTestRequiredDto::validateAndCreate([
                    'name' => '']
                )
            )
                ->toThrow(ValidationException::class);
        });
    });

    describe('Fast-Path Optimization', function(): void {
        it('skips validation when no validation attributes present', function(): void {
            // Use a DTO without validation attributes
            $dto = ValidationAttributesTestEmailDto::from(['email' => 'test@example.com']);

            // Remove validation attributes temporarily by using a simple DTO
            $simpleClass = new class('John') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $simpleClass->validateInstance();
            expect($result->isValid())->toBeTrue();
        });
    });
});
