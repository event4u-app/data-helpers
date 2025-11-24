<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\Base64Encode;
use event4u\DataHelpers\SimpleDto\Attributes\Lowercase;
use event4u\DataHelpers\SimpleDto\Attributes\Uppercase;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;

/**
 * Regression tests for Transform Attributes.
 *
 * These tests ensure that bugs found during development don't reoccur.
 */
describe('Transform Attribute - Regression Tests', function (): void {
    describe('Bug: Transform attributes applied twice on promoted constructor properties', function (): void {
        it('applies Base64Encode only once on promoted property', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Base64Encode]
                    public readonly string $token,
                ) {}
            };

            $dto = $dtoClass::from(['token' => 'hello']);

            // Should encode only once: 'hello' -> 'aGVsbG8='
            // Bug was: encoded twice: 'hello' -> 'aGVsbG8=' -> 'YUdWc2JHBD0='
            expect($dto->token)->toBe(base64_encode('hello'));
            expect($dto->token)->toBe('aGVsbG8=');
        });

        it('applies Sanitize only once on promoted property', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '<p>Hello</p>']);

            // Should sanitize only once: '<p>Hello</p>' -> 'Hello'
            expect($dto->text)->toBe('Hello');
        });

        it('applies Trim only once on promoted property', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '  hello  ']);

            // Should trim only once: '  hello  ' -> 'hello'
            expect($dto->text)->toBe('hello');
        });

        it('applies multiple transforms only once each on promoted property', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Lowercase]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '  <p>HELLO</p>  ']);

            // Each transform should be applied only once:
            // Sanitize: '  <p>HELLO</p>  ' -> '  HELLO  '
            // Trim: '  HELLO  ' -> 'HELLO'
            // Lowercase: 'HELLO' -> 'hello'
            expect($dto->text)->toBe('hello');
        });

        it('applies Base64Encode only once in UltraFast mode', function (): void {
            $dtoClass = new #[UltraFast] class ('') extends SimpleDto {
                public function __construct(
                    #[Base64Encode]
                    public readonly string $token,
                ) {}
            };

            $dto = $dtoClass::from(['token' => 'hello']);

            // Should encode only once, even in UltraFast mode
            expect($dto->token)->toBe(base64_encode('hello'));
            expect($dto->token)->toBe('aGVsbG8=');
        });
    });

    describe('Property-Level overrides Class-Level with same attribute', function (): void {
        it('Property-Level Sanitize overrides Class-Level Sanitize completely', function (): void {
            $dtoClass = new #[Sanitize(stripHtml: true)] class ('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $text1,
                    #[Sanitize(stripHtml: false)]
                    public readonly string $text2,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => '<p>Hello</p>',
                'text2' => '<p>World</p>',
            ]);

            // text1: Class-Level Sanitize strips HTML
            expect($dto->text1)->toBe('Hello');
            // text2: Property-Level Sanitize keeps HTML (overrides Class-Level)
            expect($dto->text2)->toBe('<p>World</p>');
        });

        it('Property-Level Trim overrides Class-Level Trim completely', function (): void {
            $dtoClass = new #[Trim] class ('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $text1,
                    #[Trim(characters: " \t\n\r_")]
                    public readonly string $text2,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => '  Hello  ',
                'text2' => '__World__',
            ]);

            // text1: Class-Level Trim removes spaces
            expect($dto->text1)->toBe('Hello');
            // text2: Property-Level Trim removes underscores (overrides Class-Level)
            expect($dto->text2)->toBe('World');
        });

        it('Property-Level completely replaces Class-Level transforms', function (): void {
            $dtoClass = new #[Sanitize] #[Trim] class ('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $text1,
                    #[Lowercase]
                    public readonly string $text2,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => '  <p>Hello</p>  ',
                'text2' => '  <p>WORLD</p>  ',
            ]);

            // text1: Class-Level Sanitize + Trim applied
            expect($dto->text1)->toBe('Hello');
            // text2: Only Property-Level Lowercase applied (Class-Level ignored)
            expect($dto->text2)->toBe('  <p>world</p>  ');
        });
    });

    describe('Transform attributes with null and non-string values', function (): void {
        it('skips transform for null values', function (): void {
            $dtoClass = new class (null) extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => null]);

            expect($dto->text)->toBeNull();
        });

        it('skips transform for empty string', function (): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Base64Encode]
                    public readonly string $token,
                ) {}
            };

            $dto = $dtoClass::from(['token' => '']);

            // Base64Encode should skip empty strings
            expect($dto->token)->toBe('');
        });

        it('skips transform for non-string values', function (): void {
            $dtoClass = new class (0) extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly int $number,
                ) {}
            };

            $dto = $dtoClass::from(['number' => 123]);

            // Sanitize should skip non-string values
            expect($dto->number)->toBe(123);
        });
    });

    describe('Transform attributes in different modes produce identical results', function (): void {
        it('Normal mode and UltraFast mode produce same result for Sanitize', function (): void {
            $normalDto = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $ultraFastDto = new #[UltraFast] class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $input = '<p>Hello World</p>';
            $normal = $normalDto::from(['text' => $input]);
            $ultraFast = $ultraFastDto::from(['text' => $input]);

            expect($normal->text)->toBe($ultraFast->text);
            expect($normal->text)->toBe('Hello World');
        });

        it('Normal mode and UltraFast mode produce same result for Trim', function (): void {
            $normalDto = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $ultraFastDto = new #[UltraFast] class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $input = '  Hello World  ';
            $normal = $normalDto::from(['text' => $input]);
            $ultraFast = $ultraFastDto::from(['text' => $input]);

            expect($normal->text)->toBe($ultraFast->text);
            expect($normal->text)->toBe('Hello World');
        });

        it('Normal mode and UltraFast mode produce same result for multiple transforms', function (): void {
            $normalDto = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Lowercase]
                    public readonly string $text,
                ) {}
            };

            $ultraFastDto = new #[UltraFast] class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Lowercase]
                    public readonly string $text,
                ) {}
            };

            $input = '  <p>HELLO WORLD</p>  ';
            $normal = $normalDto::from(['text' => $input]);
            $ultraFast = $ultraFastDto::from(['text' => $input]);

            expect($normal->text)->toBe($ultraFast->text);
            expect($normal->text)->toBe('hello world');
        });
    });
});

