<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\Lowercase;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;
use event4u\DataHelpers\SimpleDto\Attributes\Trim;
use event4u\DataHelpers\SimpleDto\Attributes\Uppercase;

describe('Transform Order - Edge Cases', function(): void {
    describe('Sanitize produces empty string', function(): void {
        it('Sanitize -> Trim -> ConvertEmptyToNull: HTML with only whitespace', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[ConvertEmptyToNull]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '<p>   </p>',
            ]);

            // Sanitize removes HTML: '   '
            // Trim removes whitespace: ''
            // ConvertEmptyToNull converts '' to null
            expect($dto->text)->toBeNull();
        });

        it('Sanitize -> Trim -> ConvertEmptyToNull: nested HTML with whitespace', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[ConvertEmptyToNull]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '<div><p><span>  </span></p></div>',
            ]);

            // Sanitize removes HTML: '  '
            // Trim removes whitespace: ''
            // ConvertEmptyToNull converts '' to null
            expect($dto->text)->toBeNull();
        });

        it('Sanitize -> ConvertEmptyToNull: without Trim, whitespace remains', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize(normalizeWhitespace: false)]
                    #[ConvertEmptyToNull]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '<p>   </p>',
            ]);

            // Sanitize removes HTML (without normalizing whitespace): '   '
            // ConvertEmptyToNull does NOT convert whitespace-only strings to null
            expect($dto->text)->toBe('   ');
        });
    });

    describe('Multiple Property-Level Transforms', function(): void {
        it('combines Sanitize + Trim + Lowercase on same property', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Lowercase]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>HELLO WORLD</p>  ',
            ]);

            // Sanitize removes HTML: '  HELLO WORLD  '
            // Trim removes whitespace: 'HELLO WORLD'
            // Lowercase converts to lowercase: 'hello world'
            expect($dto->text)->toBe('hello world');
        });

        it('combines Sanitize + Trim + Uppercase on same property', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[Uppercase]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  <p>hello world</p>  ',
            ]);

            // Sanitize removes HTML: '  hello world  '
            // Trim removes whitespace: 'hello world'
            // Uppercase converts to uppercase: 'HELLO WORLD'
            expect($dto->text)->toBe('HELLO WORLD');
        });

        it('Trim + Lowercase without Sanitize', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    #[Lowercase]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '  HELLO WORLD  ',
            ]);

            // Trim removes whitespace: 'HELLO WORLD'
            // Lowercase converts to lowercase: 'hello world'
            expect($dto->text)->toBe('hello world');
        });
    });

    describe('Class-Level with Property-Level Override', function(): void {
        it('Property-Level Sanitize overrides Class-Level Sanitize with different options', function(): void {
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

            // text1: Class-Level Sanitize strips HTML: 'Hello'
            // text2: Property-Level Sanitize keeps HTML: '<p>World</p>'
            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('<p>World</p>');
        });

        it('Property-Level Trim overrides Class-Level Trim with custom characters', function(): void {
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

            // text1: Class-Level Trim removes spaces: 'Hello'
            // text2: Property-Level Trim removes underscores: 'World'
            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('World');
        });

        it('Property-Level transforms override Class-Level completely', function(): void {
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

            // text1: Class-Level Sanitize + Trim: 'Hello'
            // text2: Property-Level Lowercase only (no Sanitize/Trim): '  <p>world</p>  '
            expect($dto->text1)->toBe('Hello');
            expect($dto->text2)->toBe('  <p>world</p>  ');
        });
    });

    describe('RTF with Transform Order', function(): void {
        it('Sanitize converts RTF then Trim removes whitespace', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Arial;}}\\f0\\fs20   Hello World   }';

            $dto = $dtoClass::from([
                'text' => $rtf,
            ]);

            // Sanitize converts RTF to plain text: '   Hello World   '
            // Trim removes whitespace: 'Hello World'
            expect($dto->text)->toBe('Hello World');
        });

        it('Sanitize converts RTF with only whitespace to empty after Trim', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    #[ConvertEmptyToNull]
                    public readonly ?string $text,
                ) {}
            };

            $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Arial;}}\\f0\\fs20      }';

            $dto = $dtoClass::from([
                'text' => $rtf,
            ]);

            // Sanitize converts RTF to plain text: '     '
            // Trim removes whitespace: ''
            // ConvertEmptyToNull converts '' to null
            expect($dto->text)->toBeNull();
        });
    });

    describe('Complex Whitespace Scenarios', function(): void {
        it('Sanitize normalizes whitespace, then Trim removes leading/trailing', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => "  Hello    \t\t  World    Test  ",
            ]);

            // Sanitize normalizes whitespace: '  Hello World Test  '
            // Trim removes leading/trailing: 'Hello World Test'
            expect($dto->text)->toBe('Hello World Test');
        });

        it('Trim without Sanitize keeps internal whitespace', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Trim]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => "  Hello    \t\t  World  ",
            ]);

            // Trim only removes leading/trailing: "Hello    \t\t  World"
            expect($dto->text)->toBe("Hello    \t\t  World");
        });
    });
});
