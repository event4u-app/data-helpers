<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

describe('Validation API', function(): void {
    describe('validateAndCreate() method', function(): void {
        it('validates and creates from array', function(): void {
            class SimpleDtoValidationApiArrayDto extends SimpleDto
            {
                public function __construct(
                    #[Required]
                    public readonly string $name,

                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationApiArrayDto::validateAndCreate([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->isValidated())->toBeTrue();
            expect($dto->isValid())->toBeTrue();
        });

        it('stores validation result in DTO', function(): void {
            class SimpleDtoValidationApiResultDto extends SimpleDto
            {
                public function __construct(
                    #[Required]
                    public readonly string $name,
                ) {
                }
            }

            $dto = SimpleDtoValidationApiResultDto::validateAndCreate(['name' => 'John']);

            $result = $dto->getLastValidationResult();
            expect($result)->not->toBeNull();
            expect($result->isValid())->toBeTrue();
            expect($result->validated())->toBe(['name' => 'John']);
        });

        it('throws ValidationException on invalid data', function(): void {
            class SimpleDtoValidationApiInvalidDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            SimpleDtoValidationApiInvalidDto::validateAndCreate([
                'email' => 'invalid-email',
            ]);
        })->throws(ValidationException::class);
    });

    describe('validate() method', function(): void {
        it('validates DTO instance and returns validated data', function(): void {
            class SimpleDtoValidationInstanceValidDto extends SimpleDto
            {
                public function __construct(
                    #[Required]
                    public readonly string $name,

                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationInstanceValidDto::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            $validated = $dto->validate();

            expect($validated)->toBe(['name' => 'John Doe', 'email' => 'john@example.com']);
            expect($dto->isValidated())->toBeTrue();
            expect($dto->isValid())->toBeTrue();
        });

        it('throws ValidationException for invalid data', function(): void {
            class SimpleDtoValidationInstanceInvalidDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationInstanceInvalidDto::fromArray([
                'email' => 'invalid-email',
            ]);

            $dto->validate();
        })->throws(ValidationException::class);

        it('stores validation result even on failure', function(): void {
            class SimpleDtoValidationInstanceFailDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationInstanceFailDto::fromArray([
                'email' => 'invalid-email',
            ]);

            try {
                $dto->validate();
            } catch (ValidationException) {
                // Expected
            }

            expect($dto->isValidated())->toBeTrue();
            expect($dto->isValid())->toBeFalse();
            expect($dto->getLastValidationResult())->not->toBeNull();
        });
    });

    describe('getValidationErrors() method', function(): void {
        it('returns empty collection for valid DTO', function(): void {
            class SimpleDtoValidationErrorsValidDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationErrorsValidDto::fromArray([
                'email' => 'john@example.com',
            ]);

            $dto->validate();

            $errors = $dto->getValidationErrors();

            expect($errors->isEmpty())->toBeTrue();
            expect($errors->count())->toBe(0);
        });

        it('returns errors collection for invalid DTO', function(): void {
            class SimpleDtoValidationErrorsInvalidDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationErrorsInvalidDto::fromArray([
                'email' => 'invalid-email',
            ]);

            try {
                $dto->validate();
            } catch (ValidationException) {
                // Expected
            }

            $errors = $dto->getValidationErrors();

            expect($errors->isNotEmpty())->toBeTrue();
            expect($errors->has('email'))->toBeTrue();
            expect($errors->count())->toBeGreaterThan(0);
        });

        it('provides convenient error access methods', function(): void {
            class SimpleDtoValidationErrorsAccessDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationErrorsAccessDto::fromArray([
                'email' => 'invalid',
            ]);

            try {
                $dto->validate();
            } catch (ValidationException) {
                // Expected
            }

            $errors = $dto->getValidationErrors();

            // Test has()
            expect($errors->has('email'))->toBeTrue();
            expect($errors->has('name'))->toBeFalse();

            // Test get()
            $emailErrors = $errors->get('email');
            expect($emailErrors)->toBeArray();
            expect(count($emailErrors))->toBeGreaterThan(0);

            // Test first()
            $firstError = $errors->first('email');
            expect($firstError)->toBeString();

            // Test fields()
            $fields = $errors->fields();
            expect($fields)->toContain('email');

            // Test messages()
            $messages = $errors->messages();
            expect($messages)->toBeArray();
            expect(count($messages))->toBeGreaterThan(0);
        });

        it('returns empty collection before validation', function(): void {
            class SimpleDtoValidationErrorsBeforeDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationErrorsBeforeDto::fromArray([
                'email' => 'invalid',
            ]);

            $errors = $dto->getValidationErrors();

            expect($errors->isEmpty())->toBeTrue();
            expect($dto->isValidated())->toBeFalse();
            expect($dto->isValid())->toBeNull();
        });
    });
});
