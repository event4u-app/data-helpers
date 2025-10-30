<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Enums\SerializationFormat;

describe('Validation API', function(): void {
    describe('validateAndCreate() with type parameter', function(): void {
        it('validates and creates from array (default)', function(): void {
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
        });

        it('validates and creates from JSON string', function(): void {
            class SimpleDtoValidationApiJsonDto extends SimpleDto
            {
                public function __construct(
                    #[Required]
                    public readonly string $name,

                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $json = json_encode([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            $dto = SimpleDtoValidationApiJsonDto::validateAndCreate($json, SerializationFormat::Json);

            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('validates and creates with different formats', function(): void {
            class SimpleDtoValidationApiFormatsDto extends SimpleDto
            {
                public function __construct(
                    #[Required]
                    public readonly string $name,
                ) {
                }
            }

            $json = json_encode(['name' => 'John']);
            $array = ['name' => 'Jane'];

            $dto1 = SimpleDtoValidationApiFormatsDto::validateAndCreate($json, SerializationFormat::Json);
            $dto2 = SimpleDtoValidationApiFormatsDto::validateAndCreate($array, SerializationFormat::Array);
            $dto3 = SimpleDtoValidationApiFormatsDto::validateAndCreate($array); // Default: Array

            expect($dto1->name)->toBe('John');
            expect($dto2->name)->toBe('Jane');
            expect($dto3->name)->toBe('Jane');
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

    describe('validateInstance() method', function(): void {
        it('validates DTO instance and returns true for valid data', function(): void {
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

            $result = $dto->validateInstance();

            expect($result)->toBeTrue();
            expect($dto->isValidated())->toBeTrue();
            expect($dto->isValid())->toBeTrue();
        });

        it('throws ValidationException for invalid data by default', function(): void {
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

            $dto->validateInstance();
        })->throws(ValidationException::class);

        it('returns false for invalid data when throwException=false', function(): void {
            class SimpleDtoValidationInstanceNoThrowDto extends SimpleDto
            {
                public function __construct(
                    #[Required, Email]
                    public readonly string $email,
                ) {
                }
            }

            $dto = SimpleDtoValidationInstanceNoThrowDto::fromArray([
                'email' => 'invalid-email',
            ]);

            $result = $dto->validateInstance(false);

            expect($result)->toBeFalse();
            expect($dto->isValidated())->toBeTrue();
            expect($dto->isValid())->toBeFalse();
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

            $dto->validateInstance();

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

            $dto->validateInstance(false);

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

            $dto->validateInstance(false);

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
