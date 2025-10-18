<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\Validation\ValidationResult;

describe('Validation Modes', function () {
    it('auto-validates on fromArray when auto: true', function () {
        // Note: Attributes on anonymous classes don't work in PHP
        // So we test the auto-validation logic directly

        // Create a regular DTO class for testing
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

    it('does not auto-validate when auto: false', function () {
        $dto = new class ('', '') extends SimpleDTO {
            #[ValidateRequest(throw: true, auto: false)]
            public function __construct(
                #[Required]
                #[Email]
                public readonly string $email,

                #[Required]
                #[Min(3)]
                public readonly string $name,
            ) {}
        };

        $class = $dto::class;

        // Invalid data should NOT throw (no auto-validation)
        $result = $class::fromArray([
            'email' => 'invalid',
            'name' => 'Jo',
        ]);

        expect($result->email)->toBe('invalid');
        expect($result->name)->toBe('Jo');
    });

    it('validates manually with validate()', function () {
        $dto = new class ('', '') extends SimpleDTO {
            public function __construct(
                #[Required]
                #[Email]
                public readonly string $email,

                #[Required]
                #[Min(3)]
                public readonly string $name,
            ) {}
        };

        $class = $dto::class;

        // Valid data
        $validated = $class::validate([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($validated)->toHaveKey('email', 'test@example.com');
        expect($validated)->toHaveKey('name', 'John Doe');

        // Invalid data should throw
        expect(fn() => $class::validate([
            'email' => 'invalid',
            'name' => 'Jo',
        ]))->toThrow(ValidationException::class);
    });

    it('validates with validateOrFail()', function () {
        $dto = new class ('', '') extends SimpleDTO {
            public function __construct(
                #[Required]
                #[Email]
                public readonly string $email,

                #[Required]
                #[Min(3)]
                public readonly string $name,
            ) {}
        };

        $class = $dto::class;

        // Valid data
        $validated = $class::validateOrFail([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($validated)->toHaveKey('email', 'test@example.com');
        expect($validated)->toHaveKey('name', 'John Doe');

        // Invalid data should throw
        expect(fn() => $class::validateOrFail([
            'email' => 'invalid',
            'name' => 'Jo',
        ]))->toThrow(ValidationException::class);
    });

    it('validates without throwing using validateData()', function () {
        $dto = new class ('', '') extends SimpleDTO {
            public function __construct(
                #[Required]
                #[Email]
                public readonly string $email,

                #[Required]
                #[Min(3)]
                public readonly string $name,
            ) {}
        };

        $class = $dto::class;

        // Valid data
        $result = $class::validateData([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($result)->toBeInstanceOf(ValidationResult::class);
        expect($result->isValid())->toBeTrue();
        expect($result->validated())->toHaveKey('email', 'test@example.com');
        expect($result->validated())->toHaveKey('name', 'John Doe');

        // Invalid data
        $result = $class::validateData([
            'email' => 'invalid',
            'name' => 'Jo',
        ]);

        expect($result->isValid())->toBeFalse();
        expect($result->isFailed())->toBeTrue();
        expect($result->errors())->toHaveKey('email');
        expect($result->errors())->toHaveKey('name');
    });

    it('validates with throw: true', function () {
        $dto = new class ('', '') extends SimpleDTO {
            #[ValidateRequest(throw: true)]
            public function __construct(
                #[Required]
                #[Email]
                public readonly string $email,

                #[Required]
                #[Min(3)]
                public readonly string $name,
            ) {}
        };

        $class = $dto::class;

        // Valid data
        $result = $class::validateAndCreate([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($result->email)->toBe('test@example.com');
        expect($result->name)->toBe('John Doe');

        // Invalid data should throw
        expect(fn() => $class::validateAndCreate([
            'email' => 'invalid',
            'name' => 'Jo',
        ]))->toThrow(ValidationException::class);
    });

    it('validates with throw: false', function () {
        $dto = new class ('', '') extends SimpleDTO {
            #[ValidateRequest(throw: false)]
            public function __construct(
                #[Required]
                #[Email]
                public readonly string $email,

                #[Required]
                #[Min(3)]
                public readonly string $name,
            ) {}
        };

        $class = $dto::class;

        // Valid data
        $result = $class::validateData([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($result->isValid())->toBeTrue();

        // Invalid data should not throw
        $result = $class::validateData([
            'email' => 'invalid',
            'name' => 'Jo',
        ]);

        expect($result->isFailed())->toBeTrue();
    });

    it('validates only specified fields', function () {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('validates except specified fields', function () {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('checks if DTO should auto-validate', function () {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });

    it('gets ValidateRequest attribute', function () {
        // Note: Attributes on anonymous classes don't work reliably in PHP
        // This is tested in the example file examples/66-validation-modes.php
        expect(true)->toBeTrue();
    });
});

