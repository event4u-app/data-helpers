<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Length;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\Exceptions\ValidationException;

/**
 * Tests to ensure that validation is performed AFTER transforms are applied.
 *
 * This is critical because transforms can change the length and content of values,
 * and validation should be performed on the transformed values, not the raw input.
 */
describe('Transform Before Validation', function (): void {
    describe('Max validation after Sanitize', function (): void {
        it('validates length after HTML is removed by Sanitize', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Max(10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  <p>Hello</p>  ' (17 characters)
            // After Sanitize: 'Hello' (5 characters) - Sanitize also trims whitespace
            // Max(10) should PASS because sanitized value is 5 characters
            $dto = $dtoClass::validateAndCreate([
                'text' => '  <p>Hello</p>  ',
            ]);

            expect($dto->text)->toBe('Hello');
        });

        it('validates length after HTML and whitespace are removed', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Max(10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  <p>Hello World</p>  ' (23 characters)
            // After Sanitize: '  Hello World  ' (15 characters)
            // After Trim: 'Hello World' (11 characters)
            // Max(10) should FAIL because trimmed value is 11 characters
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '  <p>Hello World</p>  ',
            ]))->toThrow(ValidationException::class);
        });

        it('validates length after Trim removes whitespace', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    #[Max(5)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  Hello  ' (9 characters)
            // After Trim: 'Hello' (5 characters)
            // Max(5) should PASS because trimmed value is 5 characters
            $dto = $dtoClass::validateAndCreate([
                'text' => '  Hello  ',
            ]);

            expect($dto->text)->toBe('Hello');
        });
    });

    describe('Min validation after Sanitize', function (): void {
        it('validates length after HTML is removed by Sanitize', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Min(5)]
                    public readonly string $text,
                ) {}
            };

            // Input: '<p>Hi</p>' (9 characters)
            // After Sanitize: 'Hi' (2 characters)
            // Min(5) should FAIL because sanitized value is only 2 characters
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '<p>Hi</p>',
            ]))->toThrow(ValidationException::class);
        });

        it('validates length after Trim removes whitespace', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    #[Min(5)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  Hi  ' (6 characters)
            // After Trim: 'Hi' (2 characters)
            // Min(5) should FAIL because trimmed value is only 2 characters
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '  Hi  ',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Required validation after Sanitize + Trim', function (): void {
        it('validates Required after Sanitize produces empty string', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Required]
                    public readonly string $text,
                ) {}
            };

            // Input: '<p></p>' (7 characters)
            // After Sanitize: '' (0 characters)
            // After Trim: '' (0 characters)
            // Required should FAIL because value is empty after transforms
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '<p></p>',
            ]))->toThrow(ValidationException::class);
        });

        it('validates Required after Trim produces empty string', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    #[Required]
                    public readonly string $text,
                ) {}
            };

            // Input: '   ' (3 characters)
            // After Trim: '' (0 characters)
            // Required should FAIL because value is empty after trim
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '   ',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Class-Level transforms before validation', function (): void {
        it('validates length after Class-Level Sanitize', function (): void {
            $dtoClass = new #[Sanitize] class ('') extends SimpleDto {
                public function __construct(
                    #[Max(10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  <p>Hello</p>  ' (17 characters)
            // After Class-Level Sanitize: 'Hello' (5 characters) - Sanitize also trims whitespace
            // Max(10) should PASS
            $dto = $dtoClass::validateAndCreate([
                'text' => '  <p>Hello</p>  ',
            ]);

            expect($dto->text)->toBe('Hello');
        });

        it('validates length after Class-Level Trim', function (): void {
            $dtoClass = new #[Trim] class ('') extends SimpleDto {
                public function __construct(
                    #[Max(5)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  Hello  ' (9 characters)
            // After Class-Level Trim: 'Hello' (5 characters)
            // Max(5) should PASS
            $dto = $dtoClass::validateAndCreate([
                'text' => '  Hello  ',
            ]);

            expect($dto->text)->toBe('Hello');
        });

        it('validates length after Class-Level Sanitize + Trim', function (): void {
            $dtoClass = new #[Sanitize] #[Trim] class ('') extends SimpleDto {
                public function __construct(
                    #[Max(10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  <p>Hello World</p>  ' (23 characters)
            // After Sanitize: '  Hello World  ' (15 characters)
            // After Trim: 'Hello World' (11 characters)
            // Max(10) should FAIL
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '  <p>Hello World</p>  ',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('validate() method applies transforms', function (): void {
        it('validate() applies transforms before validation', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Max(10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  <p>Hello</p>  ' (17 characters)
            // After Sanitize + Trim: 'Hello' (5 characters)
            // Max(10) should PASS
            $result = $dtoClass::validate([
                'text' => '  <p>Hello</p>  ',
            ]);

            expect($result->isValid())->toBeTrue();
        });

        it('validate() returns transformed data in validated()', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $result = $dtoClass::validate([
                'text' => '  <p>Hello</p>  ',
            ]);

            expect($result->isValid())->toBeTrue();
            // The validated data should contain the transformed value
            expect($result->validated()['text'])->toBe('Hello');
        });
    });

    describe('Length validation after transforms', function (): void {
        it('validates length after Sanitize removes HTML', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Length(3, 10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '<p>Hello World</p>' (18 characters)
            // After Sanitize: 'Hello World' (11 characters)
            // Length(3, 10) should FAIL because sanitized value is 11 characters
            expect(fn() => $dtoClass::validateAndCreate([
                'text' => '<p>Hello World</p>',
            ]))->toThrow(ValidationException::class);
        });

        it('validates length after Sanitize + Trim', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Length(3, 10)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  <p>Hello</p>  ' (17 characters)
            // After Sanitize + Trim: 'Hello' (5 characters)
            // Length(3, 10) should PASS because transformed value is 5 characters
            $dto = $dtoClass::validateAndCreate([
                'text' => '  <p>Hello</p>  ',
            ]);

            expect($dto->text)->toBe('Hello');
        });

        it('validates max length after Trim', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    #[Length(5)]
                    public readonly string $text,
                ) {}
            };

            // Input: '  Hello  ' (9 characters)
            // After Trim: 'Hello' (5 characters)
            // Length(5) should PASS because trimmed value is exactly 5 characters
            $dto = $dtoClass::validateAndCreate([
                'text' => '  Hello  ',
            ]);

            expect($dto->text)->toBe('Hello');
        });
    });
});

