<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Convert;
use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;

describe('Convert Attribute', function(): void {
    describe('RTF to Text', function(): void {
        it('converts RTF to plain text', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
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
                    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '{\\rtf1\\ansi Test \\u252? Text}']);

            expect($dto->text)
                ->toContain('Test')
                ->toContain('ü')
                ->toContain('Text');
        });

        it('handles empty RTF', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '']);
            expect($dto->text)->toBe('');
        });
    });

    describe('RTF to HTML', function(): void {
        it('converts RTF to HTML', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '{\\rtf1\\ansi Hello\\line World}',
            ]);

            expect($dto->content)
                ->toContain('Hello')
                ->toContain('World')
                ->toContain('<br>')
                ->not->toContain('{\rtf')
                ->not->toContain('\line');
        });
    });

    describe('HTML to Text', function(): void {
        it('converts HTML to plain text', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<p>Hello <strong>World</strong></p>',
            ]);

            expect($dto->content)->toBe('Hello World');
        });

        it('decodes HTML entities', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Hello &amp; Goodbye &lt;tag&gt;']);

            expect($dto->text)->toBe('Hello & Goodbye <tag>');
        });

        it('removes HTML tags including script tags', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '<script>alert("xss")</script>Hello']);

            // strip_tags removes tags but keeps content
            expect($dto->text)
                ->toContain('Hello')
                ->not->toContain('<script')
                ->not->toContain('</script>');
        });
    });

    describe('HTML to RTF', function(): void {
        it('converts HTML to RTF', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::RTF)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<p>Hello World</p>',
            ]);

            expect($dto->content)
                ->toStartWith('{\rtf')
                ->toContain('Hello World')
                ->toEndWith('}');
        });
    });

    describe('Text to HTML', function(): void {
        it('converts text to HTML with XSS protection', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<script>alert("xss")</script>Hello',
            ]);

            expect($dto->content)
                ->toContain('&lt;script&gt;')
                ->toContain('Hello')
                ->not->toContain('<script>');
        });

        it('converts newlines to br tags by default', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => "Line 1\nLine 2",
            ]);

            expect($dto->content)
                ->toContain('Line 1<br>')
                ->toContain('Line 2');
        });

        it('can disable nl2br conversion', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML, nl2br: false)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => "Line 1\nLine 2",
            ]);

            expect($dto->content)
                ->not->toContain('<br>')
                ->toContain("\n");
        });

        it('escapes HTML entities', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Hello & Goodbye']);

            expect($dto->text)->toBe('Hello &amp; Goodbye');
        });
    });

    describe('Text to RTF', function(): void {
        it('converts text to RTF', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => 'Hello World',
            ]);

            expect($dto->content)
                ->toStartWith('{\rtf')
                ->toContain('Hello World')
                ->toEndWith('}');
        });

        it('escapes RTF special characters', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Test { } \\ chars']);

            expect($dto->text)
                ->toContain('\\{')
                ->toContain('\\}')
                ->toContain('\\\\');
        });

        it('converts newlines to RTF line breaks', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => "Line 1\nLine 2"]);

            expect($dto->text)->toContain('\\line');
        });

        it('encodes unicode characters', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => 'Hällö Wörld']);

            expect($dto->text)
                ->toContain('\\u')
                ->toStartWith('{\rtf');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles null values', function(): void {
            $dtoClass = new class (null) extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly ?string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => null]);
            expect($dto->text)->toBeNull();
        });

        it('handles empty strings', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $text,
                ) {}
            };

            $dto = $dtoClass::from(['text' => '']);
            expect($dto->text)->toBe('');
        });

        it('handles non-string values', function(): void {
            $dtoClass = new class (0) extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly int $number,
                ) {}
            };

            $dto = $dtoClass::from(['number' => 123]);
            expect($dto->number)->toBe(123);
        });
    });

    describe('XSS Protection', function(): void {
        it('protects against XSS in text to HTML conversion', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            // Test script tag
            $dto = $dtoClass::from(['content' => '<script>alert("xss")</script>']);
            expect($dto->content)
                ->not->toContain('<script')
                ->toContain('&lt;script&gt;');

            // Test img tag with onerror
            $dto = $dtoClass::from(['content' => '<img src=x onerror="alert(1)">']);
            expect($dto->content)
                ->not->toContain('<img')
                ->toContain('&lt;img');

            // Test svg tag with onload
            $dto = $dtoClass::from(['content' => '<svg onload="alert(1)">']);
            expect($dto->content)
                ->not->toContain('<svg')
                ->toContain('&lt;svg');
        });

        it('protects against XSS in HTML to text conversion', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<script>alert("xss")</script><p>Safe Content</p>',
            ]);

            // strip_tags removes tags but keeps content, so we check that tags are removed
            expect($dto->content)
                ->toContain('Safe Content')
                ->not->toContain('<script')
                ->not->toContain('<p>');
        });

        it('protects against XSS in RTF to HTML conversion', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '{\\rtf1\\ansi <script>alert("xss")</script>}',
            ]);

            expect($dto->content)
                ->not->toContain('<script')
                ->toContain('&lt;script&gt;');
        });
    });

    describe('Round-trip Conversions', function(): void {
        it('text → HTML → text preserves content', function(): void {
            $original = "Hello World\nLine 2";

            $dtoClass1 = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dtoClass2 = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $content,
                ) {}
            };

            $html = $dtoClass1::from(['content' => $original])->content;
            $text = $dtoClass2::from(['content' => $html])->content;

            expect($text)->toContain('Hello World');
            expect($text)->toContain('Line 2');
        });

        it('text → RTF → text preserves content', function(): void {
            $original = "Hello World\nLine 2";

            $dtoClass1 = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]
                    public readonly string $content,
                ) {}
            };

            $dtoClass2 = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
                    public readonly string $content,
                ) {}
            };

            $rtf = $dtoClass1::from(['content' => $original])->content;
            $text = $dtoClass2::from(['content' => $rtf])->content;

            expect($text)->toContain('Hello World');
            expect($text)->toContain('Line 2');
        });
    });

    describe('Enum Syntax', function(): void {
        it('works with TextFormat enum for RTF to Text', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
                    public readonly string $description,
                ) {}
            };

            $dto = $dtoClass::from([
                'description' => '{\\rtf1\\ansi Hello World}',
            ]);

            expect($dto->description)->toContain('Hello World');
        });

        it('works with TextFormat enum for HTML to Text', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<p>Hello <strong>World</strong></p>',
            ]);

            expect($dto->content)->toBe('Hello World');
        });

        it('works with TextFormat enum for Text to HTML', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<script>alert("xss")</script>',
            ]);

            expect($dto->content)
                ->not->toContain('<script')
                ->toContain('&lt;script&gt;');
        });

        it('works with TextFormat enum for RTF to HTML', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::HTML)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '{\\rtf1\\ansi Hello\\line World}',
            ]);

            expect($dto->content)
                ->toContain('Hello')
                ->toContain('World')
                ->toContain('<br>');
        });

        it('works with TextFormat enum for HTML to RTF', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::HTML, ConvertFormat::RTF)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => '<p>Hello World</p>',
            ]);

            expect($dto->content)
                ->toStartWith('{\rtf')
                ->toContain('Hello World')
                ->toEndWith('}');
        });

        it('works with TextFormat enum for Text to RTF', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'content' => 'Hello World',
            ]);

            expect($dto->content)
                ->toStartWith('{\rtf')
                ->toContain('Hello World')
                ->toEndWith('}');
        });

        it('can mix enum and string syntax', function(): void {
            $dtoClass = new class ('', '') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
                    public readonly string $description,
                    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
                    public readonly string $content,
                ) {}
            };

            $dto = $dtoClass::from([
                'description' => '{\\rtf1\\ansi RTF Content}',
                'content' => '<p>HTML Content</p>',
            ]);

            expect($dto->description)->toContain('RTF Content');
            expect($dto->content)->toBe('HTML Content');
        });

        it('validates enum values in constructor', function(): void {
            $dtoClass = new class ('') extends SimpleDto {
                public function __construct(
                    #[Convert(ConvertFormat::TEXT, ConvertFormat::TEXT)]
                    public readonly string $text,
                ) {}
            };

            expect(fn(): object => $dtoClass::from(['text' => 'test']))
                ->toThrow(InvalidArgumentException::class, 'Source and target format cannot be the same');
        });
    });
});
