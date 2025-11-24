<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Lowercase;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Attributes\Uppercase;

/**
 * Advanced tests for Transform Attributes.
 *
 * These tests cover complex scenarios and edge cases.
 */
describe('Transform Attribute - Advanced Tests', function(): void {
    describe('Class-Level transforms on mixed property types', function(): void {
        it('applies Class-Level Sanitize only to string properties', function(): void {
            $dtoClass = new #[Sanitize] class ('', 0, 0.0, false) extends SimpleDto {
                public function __construct(
                    public readonly string $text,
                    public readonly int $number,
                    public readonly float $decimal,
                    public readonly bool $flag,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '<p>Hello</p>',
                'number' => 123,
                'decimal' => 45.67,
                'flag' => true,
            ]);

            // Sanitize should only be applied to string property
            expect($dto->text)->toBe('Hello');
            expect($dto->number)->toBe(123);
            expect($dto->decimal)->toBe(45.67);
            expect($dto->flag)->toBe(true);
        });

        it('applies Class-Level Trim only to string properties', function(): void {
            $dtoClass = new #[Trim] class ('', '', 0, null) extends SimpleDto {
                public function __construct(
                    public readonly string $text1,
                    public readonly string $text2,
                    public readonly int $number,
                    public readonly ?string $nullable,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => '  Hello  ',
                'text2' => '  World  ',
                'number' => 123,
                'nullable' => null,
            ]);

            // Trim should only be applied to string properties
            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('World');
            expect($dto->number)->toBe(123);
            expect($dto->nullable)->toBeNull();
        });

        it('applies Class-Level transforms to all string properties', function(): void {
            $dtoClass = new #[Sanitize] #[Trim] class ('', '', '', 0) extends SimpleDto {
                public function __construct(
                    public readonly string $title,
                    public readonly string $description,
                    public readonly string $content,
                    public readonly int $id,
                ) {}
            };

            $dto = $dtoClass::from([
                'title' => '  <p>Title</p>  ',
                'description' => '  <p>Description</p>  ',
                'content' => '  <p>Content</p>  ',
                'id' => 123,
            ]);

            // Sanitize + Trim should be applied to all string properties
            expect($dto->title)->toBe('Title');
            expect($dto->description)->toBe('Description');
            expect($dto->content)->toBe('Content');
            expect($dto->id)->toBe(123);
        });
    });

    describe('Transform attributes with MapFrom', function(): void {
        it('applies transforms after mapping with MapFrom', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    #[Sanitize]
                    #[Trim]
                    public readonly string $name,
                ) {}
            };

            $dto = $dtoClass::from([
                'user_name' => '  <p>John Doe</p>  ',
            ]);

            // MapFrom should map first, then Sanitize + Trim
            expect($dto->name)->toBe('John Doe');
        });

        it('applies Class-Level transforms after mapping with MapFrom', function(): void {
            $dtoClass = new #[Sanitize] #[Trim] class ('', '') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name,
                    #[MapFrom('user_email')]
                    public readonly string $email,
                ) {}
            };

            $dto = $dtoClass::from([
                'user_name' => '  <p>John Doe</p>  ',
                'user_email' => '  <p>john@example.com</p>  ',
            ]);

            // MapFrom should map first, then Class-Level Sanitize + Trim
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('Property-Level transforms override Class-Level with MapFrom', function(): void {
            $dtoClass = new #[Sanitize] class ('', '') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name,
                    #[MapFrom('user_email')]
                    #[Lowercase]
                    public readonly string $email,
                ) {}
            };

            $dto = $dtoClass::from([
                'user_name' => '<p>John Doe</p>',
                'user_email' => '<p>JOHN@EXAMPLE.COM</p>',
            ]);

            // name: MapFrom + Class-Level Sanitize
            expect($dto->name)->toBe('John Doe');
            // email: MapFrom + Property-Level Lowercase (Class-Level ignored)
            expect($dto->email)->toBe('<p>john@example.com</p>');
        });
    });

    describe('Transform attribute priority and sorting', function(): void {
        it('applies Sanitize before Trim before Lowercase', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Lowercase]
                    #[Trim]
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>HELLO WORLD</p>  ',
            ]);

            // Despite declaration order, should apply: Sanitize -> Trim -> Lowercase
            // Sanitize: '  <p>HELLO WORLD</p>  ' -> '  HELLO WORLD  '
            // Trim: '  HELLO WORLD  ' -> 'HELLO WORLD'
            // Lowercase: 'HELLO WORLD' -> 'hello world'
            expect($dto->text)->toBe('hello world');
        });

        it('applies Sanitize before Trim before Uppercase', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Uppercase]
                    #[Sanitize]
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>hello world</p>  ',
            ]);

            // Despite declaration order, should apply: Sanitize -> Trim -> Uppercase
            // Sanitize: '  <p>hello world</p>  ' -> '  hello world  '
            // Trim: '  hello world  ' -> 'hello world'
            // Uppercase: 'hello world' -> 'HELLO WORLD'
            expect($dto->text)->toBe('HELLO WORLD');
        });

        it('applies Class-Level transforms in correct order', function(): void {
            $dtoClass = new #[Trim] #[Sanitize] class ('') extends SimpleDto {
                public function __construct(
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>HELLO WORLD</p>  ',
            ]);

            // Despite declaration order, should apply: Sanitize -> Trim
            expect($dto->text)->toBe('HELLO WORLD');
        });

        it('maintains priority even with multiple properties', function(): void {
            $dtoClass = new class ('', '') extends SimpleDto {
                public function __construct(
                    #[Lowercase]
                    #[Trim]
                    #[Sanitize]
                    public readonly string $text1,
                    #[Uppercase]
                    #[Sanitize]
                    #[Trim]
                    public readonly string $text2,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => '  <p>HELLO</p>  ',
                'text2' => '  <p>world</p>  ',
            ]);

            // text1: Sanitize -> Trim -> Lowercase
            expect($dto->text1)->toBe('hello');
            // text2: Sanitize -> Trim -> Uppercase
            expect($dto->text2)->toBe('WORLD');
        });
    });

    describe('Transform attributes with nullable properties', function(): void {
        it('handles nullable string with null value', function(): void {
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

        it('handles nullable string with empty string', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '']);

            // Empty string should remain empty (not converted to null)
            expect($dto->text)->toBe('');
        });

        it('handles nullable string with whitespace-only value', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '   ']);

            // Sanitize normalizes and trims: '   ' -> ''
            expect($dto->text)->toBe('');
        });

        it('handles Class-Level transforms on nullable properties', function(): void {
            $dtoClass = new #[Sanitize] #[Trim] class (null, '') extends SimpleDto {
                public function __construct(
                    public readonly ?string $text1,
                    public readonly ?string $text2,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => null,
                'text2' => '  <p>Hello</p>  ',
            ]);

            expect($dto->text1)->toBeNull();
            expect($dto->text2)->toBe('Hello');
        });
    });

    describe('Transform attributes in UltraFast mode with mixed scenarios', function(): void {
        it('applies transforms correctly with MapFrom in UltraFast mode', function(): void {
            $dtoClass = new #[UltraFast] class ('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    #[Sanitize]
                    #[Trim]
                    public readonly string $name,
                ) {}
            };

            $dto = $dtoClass::from([
                'user_name' => '  <p>John Doe</p>  ',
            ]);

            expect($dto->name)->toBe('John Doe');
        });

        it('applies Class-Level transforms on mixed types in UltraFast mode', function(): void {
            $dtoClass = new #[UltraFast] #[Sanitize] #[Trim] class ('', 0) extends SimpleDto {
                public function __construct(
                    public readonly string $text,
                    public readonly int $number,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>Hello</p>  ',
                'number' => 123,
            ]);

            expect($dto->text)->toBe('Hello');
            expect($dto->number)->toBe(123);
        });
    });
});
