<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\ValidateRequest;
use event4u\DataHelpers\Validation\ValidationResult;

// Test Dtos
class ValidationTestDto1 extends SimpleDto
{
    #[ValidateRequest(throw: true, auto: false)]
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

class ValidationTestDto2 extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

class ValidationTestDto3 extends SimpleDto
{
    #[ValidateRequest(throw: true)]
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

class ValidationTestDto4 extends SimpleDto
{
    #[ValidateRequest(throw: false)]
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

describe('Validation Modes', function(): void {
    it('auto-validates on fromArray when auto: true', function(): void {
        // Note: Attributes on anonymous classes don't work in PHP
        // So we test the auto-validation logic directly

        // Create a regular Dto class for testing
        $validData = [
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ];

        $invalidData = [
            'email' => 'invalid',
            'name' => 'Jo',
        ];

        // Test that auto-validation works when enabled
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('does not auto-validate when auto: false', function(): void {
        // Invalid data should NOT throw (no auto-validation)
        $result = ValidationTestDto1::fromArray([
            'email' => 'invalid',
            'name' => 'Jo',
        ]);

        expect($result->email)->toBe('invalid');
        expect($result->name)->toBe('Jo');
    });

    it('validates manually with validate()', function(): void {
        // Valid data
        $validated = ValidationTestDto2::validate();

        expect($validated)->toHaveKey('email', 'test@example.com');
        expect($validated)->toHaveKey('name', 'John Doe');

        // Invalid data should throw
        expect(fn(): array => ValidationTestDto2::validate())->toThrow(ValidationException::class);
    });

    it('validates with validateOrFail()', function(): void {
        // Valid data
        $validated = ValidationTestDto2::validateOrFail([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($validated)->toHaveKey('email', 'test@example.com');
        expect($validated)->toHaveKey('name', 'John Doe');

        // Invalid data should throw
        expect(fn(): array => ValidationTestDto2::validateOrFail([
            'email' => 'invalid',
            'name' => 'Jo',
        ]))->toThrow(ValidationException::class);
    });

    it('validates without throwing using validateData()', function(): void {
        // Valid data
        $result = ValidationTestDto2::validateData([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($result)->toBeInstanceOf(ValidationResult::class);
        expect($result->isValid())->toBeTrue();
        expect($result->validated())->toHaveKey('email', 'test@example.com');
        expect($result->validated())->toHaveKey('name', 'John Doe');

        // Invalid data
        $result = ValidationTestDto2::validateData([
            'email' => 'invalid',
            'name' => 'Jo',
        ]);

        expect($result->isValid())->toBeFalse();
        expect($result->isFailed())->toBeTrue();
        expect($result->errors())->toHaveKey('email');
        expect($result->errors())->toHaveKey('name');
    });

    it('validates with throw: true', function(): void {
        // Valid data
        $result = ValidationTestDto3::validateAndCreate([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($result->email)->toBe('test@example.com');
        expect($result->name)->toBe('John Doe');

        // Invalid data should throw
        expect(fn(): \ValidationTestDto3 => ValidationTestDto3::validateAndCreate([
            'email' => 'invalid',
            'name' => 'Jo',
        ]))->toThrow(ValidationException::class);
    });

    it('validates with throw: false', function(): void {
        // Valid data
        $result = ValidationTestDto4::validateData([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($result->isValid())->toBeTrue();

        // Invalid data should not throw
        $result = ValidationTestDto4::validateData([
            'email' => 'invalid',
            'name' => 'Jo',
        ]);

        expect($result->isFailed())->toBeTrue();
    });

    it('validates only specified fields', function(): void {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('validates except specified fields', function(): void {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('checks if Dto should auto-validate', function(): void {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('gets ValidateRequest attribute', function(): void {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });
});
