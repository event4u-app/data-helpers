<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;

describe('Sanitize Attribute', function(): void {
    describe('RTF Conversion', function(): void {
        it('converts RTF to plain text', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $description,
                ) {}
            };

            $dto = $dtoClass::from([
                'description' => "{\\rtf1\\ansi\\deff0{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}}" .
                    "\\viewkind4\\uc1\\pard\\lang1031\\fs20 Einfassungen, Gossen, Einzelabl\\'e4ufe und \\line Rinnen \\par}",
            ]);

            expect($dto->description)
                ->toContain('Einfassungen')
                ->toContain('Gossen')
                ->toContain('Einzelabläufe')
                ->toContain('Rinnen')
                ->not->toContain('{\rtf')
                ->not->toContain('\line')
                ->not->toContain('\par');
        });

        it('handles RTF with unicode escapes', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '{\\rtf1\\ansi Test \\u252? Text}']);

            expect($dto->text)
                ->toContain('Test')
                ->toContain('ü')
                ->toContain('Text');
        });

        it('handles RTF with hex escapes', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "{\\rtf1\\ansi Test \\'e4\\'f6\\'fc Text}"]);

            expect($dto->text)
                ->toContain('Test')
                ->toContain('ä')
                ->toContain('ö')
                ->toContain('ü')
                ->toContain('Text');
        });
    });

    describe('HTML Handling', function(): void {
        it('strips HTML tags by default', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '<p>Hello <strong>World</strong></p>']);

            expect($dto->text)
                ->toBe('Hello World')
                ->not->toContain('<p>')
                ->not->toContain('<strong>');
        });

        it('keeps HTML when stripHtml is false', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize(stripHtml: false)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '<p>Hello <strong>World</strong></p>']);

            expect($dto->text)
                ->toContain('<p>')
                ->toContain('<strong>')
                ->toContain('Hello')
                ->toContain('World');
        });

        it('decodes HTML entities by default', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize(stripHtml: false)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Test &amp; &lt;tag&gt; &quot;quotes&quot;']);

            expect($dto->text)
                ->toBe('Test & <tag> "quotes"');
        });

        it('keeps HTML entities when decodeHtmlEntities is false', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize(decodeHtmlEntities: false)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Test &amp; &lt;tag&gt;']);

            expect($dto->text)
                ->toContain('&amp;')
                ->toContain('&lt;')
                ->toContain('&gt;');
        });
    });

    describe('Whitespace Normalization', function(): void {
        it('normalizes multiple spaces to single space', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Hello    World     Test']);

            expect($dto->text)->toBe('Hello World Test');
        });

        it('normalizes line endings', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "Line1\r\nLine2\rLine3\nLine4"]);

            expect($dto->text)->toBe("Line1\nLine2\nLine3\nLine4");
        });

        it('removes excessive blank lines', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "Line1\n\n\n\n\nLine2"]);

            expect($dto->text)->toBe("Line1\n\nLine2");
        });

        it('skips whitespace normalization when disabled', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize(normalizeWhitespace: false)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Hello    World']);

            expect($dto->text)->toBe('Hello    World');
        });
    });

    describe('Control Characters', function(): void {
        it('removes control characters by default', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "Hello\x00\x01\x02World"]);

            expect($dto->text)->toBe('HelloWorld');
        });

        it('keeps newlines but normalizes tabs to spaces', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "Hello\tWorld\nTest"]);

            expect($dto->text)->toBe("Hello World\nTest");
        });

        it('skips control character removal when disabled', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize(stripHtml: false, removeControlChars: false)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "Hello\x00World"]);

            expect($dto->text)->toContain("\x00");
        });
    });

    describe('Class-Level Application', function(): void {
        it('applies to all string properties when on class', function(): void {
            $dtoClass = new #[Sanitize] class ('', '', 0) extends SimpleDto {
                public function __construct(
                    public readonly string $name,
                    public readonly string $bio,
                    public readonly int $age,
                ) {}
            };

            $dto = $dtoClass::from([
                'name' => '<p>Name</p>',
                'bio' => '<strong>Bio</strong>',
                'age' => 25,
            ]);

            expect($dto->name)->toBe('Name');
            expect($dto->bio)->toBe('Bio');
            expect($dto->age)->toBe(25);
        });

        it('does not apply to numeric properties', function(): void {
            $dtoClass = new #[Sanitize] class ('', 0, 0.0) extends SimpleDto {
                public function __construct(
                    public readonly string $text,
                    public readonly int $number,
                    public readonly float $decimal,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '<p>Text</p>',
                'number' => 123,
                'decimal' => 45.67,
            ]);

            expect($dto->text)->toBe('Text');
            expect($dto->number)->toBe(123);
            expect($dto->decimal)->toBe(45.67);
        });

        it('property-level overrides class-level', function(): void {
            $dtoClass = new #[Sanitize(stripHtml: true)] class ('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $text1,
                    #[Sanitize(stripHtml: false)]
                    public readonly string $text2,
                ) {}
            };

            $dto = $dtoClass::from([
                'text1' => '<p>Stripped</p>',
                'text2' => '<strong>Kept</strong>',
            ]);

            expect($dto->text1)->toBe('Stripped');
            expect($dto->text2)->toContain('<strong>');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles null values', function(): void {
            $dtoClass = new class (null) extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => null]);

            expect($dto->text)->toBeNull();
        });

        it('handles empty strings', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '']);

            expect($dto->text)->toBe('');
        });

        it('handles non-string values gracefully', function(): void {
            $dtoClass = new class ('', []) extends SimpleDto {
                public function __construct(
                    #[Sanitize]
                    public readonly string $text,
                    /** @var array<mixed> */
                    public readonly array $data,
                ) {}
            };

            $dto = $dtoClass::from([
                'text' => '<p>Text</p>',
                'data' => ['array'],
            ]);

            expect($dto->text)->toBe('Text');
            expect($dto->data)->toBe(['array']);
        });
    });
});
