<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto\Support\FormatConverter;

describe('FormatConverter', function (): void {
    describe('detectFormat', function (): void {
        it('detects RTF format', function (): void {
            expect(FormatConverter::detectFormat('{\rtf1\ansi Hello}'))->toBe('rtf');
            expect(FormatConverter::detectFormat('  {\rtf1 Test}'))->toBe('rtf');
        });

        it('detects HTML format', function (): void {
            expect(FormatConverter::detectFormat('<p>Hello</p>'))->toBe('html');
            expect(FormatConverter::detectFormat('<div>Test</div>'))->toBe('html');
            expect(FormatConverter::detectFormat('  <span>Test</span>'))->toBe('html');
        });

        it('detects plain text format', function (): void {
            expect(FormatConverter::detectFormat('Hello World'))->toBe('text');
            expect(FormatConverter::detectFormat('Just plain text'))->toBe('text');
        });
    });

    describe('rtfToText', function (): void {
        it('converts simple RTF to text', function (): void {
            $rtf = '{\rtf1\ansi Hello World}';
            expect(FormatConverter::rtfToText($rtf))->toContain('Hello World');
        });

        it('handles RTF line breaks', function (): void {
            $rtf = '{\rtf1\ansi Line1\line Line2\par Line3}';
            $text = FormatConverter::rtfToText($rtf);

            expect($text)
                ->toContain('Line1')
                ->toContain('Line2')
                ->toContain('Line3');
        });

        it('handles RTF unicode escapes', function (): void {
            $rtf = '{\rtf1\ansi Test \u252? Text}';
            expect(FormatConverter::rtfToText($rtf))->toContain('ü');
        });

        it('handles RTF hex escapes', function (): void {
            $rtf = "{\rtf1\ansi Test \'e4\'f6\'fc Text}";
            $text = FormatConverter::rtfToText($rtf);

            expect($text)
                ->toContain('ä')
                ->toContain('ö')
                ->toContain('ü');
        });
    });

    describe('rtfToHtml', function (): void {
        it('converts RTF to HTML', function (): void {
            $rtf = '{\rtf1\ansi Hello\line World}';
            $html = FormatConverter::rtfToHtml($rtf);

            expect($html)
                ->toContain('Hello')
                ->toContain('World')
                ->toContain('<br>');
        });

        it('escapes HTML in RTF content', function (): void {
            $rtf = '{\rtf1\ansi <script>alert("xss")</script>}';
            $html = FormatConverter::rtfToHtml($rtf);

            expect($html)
                ->not->toContain('<script')
                ->toContain('&lt;script&gt;');
        });
    });

    describe('htmlToText', function (): void {
        it('removes HTML tags', function (): void {
            $html = '<p>Hello <strong>World</strong></p>';
            expect(FormatConverter::htmlToText($html))->toBe('Hello World');
        });

        it('decodes HTML entities', function (): void {
            $html = 'Hello &amp; Goodbye &lt;tag&gt;';
            expect(FormatConverter::htmlToText($html))->toBe('Hello & Goodbye <tag>');
        });

        it('normalizes whitespace', function (): void {
            $html = '<p>Hello    World</p>';
            expect(FormatConverter::htmlToText($html))->toBe('Hello World');
        });

        it('removes script tags', function (): void {
            $html = '<script>alert("xss")</script><p>Safe</p>';
            $text = FormatConverter::htmlToText($html);

            expect($text)->toContain('Safe');
        });
    });

    describe('htmlToRtf', function (): void {
        it('converts HTML to RTF', function (): void {
            $html = '<p>Hello World</p>';
            $rtf = FormatConverter::htmlToRtf($html);

            expect($rtf)
                ->toStartWith('{\rtf')
                ->toContain('Hello World')
                ->toEndWith('}');
        });

        it('removes HTML tags before conversion', function (): void {
            $html = '<p>Hello <strong>World</strong></p>';
            $rtf = FormatConverter::htmlToRtf($html);

            expect($rtf)
                ->toContain('Hello World')
                ->not->toContain('<p>')
                ->not->toContain('<strong>');
        });
    });

    describe('textToHtml', function (): void {
        it('escapes HTML special characters', function (): void {
            $text = '<script>alert("xss")</script>';
            $html = FormatConverter::textToHtml($text);

            expect($html)
                ->not->toContain('<script')
                ->toContain('&lt;script&gt;');
        });

        it('converts newlines to br tags by default', function (): void {
            $text = "Line 1\nLine 2";
            $html = FormatConverter::textToHtml($text);

            expect($html)
                ->toContain('Line 1<br>')
                ->toContain('Line 2');
        });

        it('can disable nl2br conversion', function (): void {
            $text = "Line 1\nLine 2";
            $html = FormatConverter::textToHtml($text, false);

            expect($html)
                ->not->toContain('<br>')
                ->toContain("\n");
        });

        it('escapes ampersands', function (): void {
            $text = 'Hello & Goodbye';
            expect(FormatConverter::textToHtml($text))->toBe('Hello &amp; Goodbye');
        });

        it('escapes quotes', function (): void {
            $text = 'Hello "World"';
            expect(FormatConverter::textToHtml($text))->toContain('&quot;');
        });
    });

    describe('textToRtf', function (): void {
        it('creates RTF document', function (): void {
            $text = 'Hello World';
            $rtf = FormatConverter::textToRtf($text);

            expect($rtf)
                ->toStartWith('{\rtf')
                ->toContain('Hello World')
                ->toEndWith('}');
        });

        it('escapes RTF special characters', function (): void {
            $text = 'Test { } \\ chars';
            $rtf = FormatConverter::textToRtf($text);

            expect($rtf)
                ->toContain('\\{')
                ->toContain('\\}')
                ->toContain('\\\\');
        });

        it('converts newlines to RTF line breaks', function (): void {
            $text = "Line 1\nLine 2";
            $rtf = FormatConverter::textToRtf($text);

            expect($rtf)->toContain('\\line');
        });

        it('converts tabs to RTF tabs', function (): void {
            $text = "Col1\tCol2";
            $rtf = FormatConverter::textToRtf($text);

            expect($rtf)->toContain('\\tab');
        });

        it('encodes unicode characters', function (): void {
            $text = 'Hällö Wörld';
            $rtf = FormatConverter::textToRtf($text);

            expect($rtf)->toContain('\\u');
        });

        it('handles emoji and special unicode', function (): void {
            $text = 'Hello 😀 World';
            $rtf = FormatConverter::textToRtf($text);

            expect($rtf)
                ->toStartWith('{\rtf')
                ->toContain('\\u');
        });
    });

    describe('Round-trip conversions', function (): void {
        it('text → HTML → text preserves content', function (): void {
            $original = "Hello World\nLine 2";
            $html = FormatConverter::textToHtml($original);
            $text = FormatConverter::htmlToText($html);

            expect($text)->toContain('Hello World');
            expect($text)->toContain('Line 2');
        });

        it('text → RTF → text preserves content', function (): void {
            $original = "Hello World\nLine 2";
            $rtf = FormatConverter::textToRtf($original);
            $text = FormatConverter::rtfToText($rtf);

            expect($text)->toContain('Hello World');
            expect($text)->toContain('Line 2');
        });

        it('HTML → RTF → HTML preserves basic content', function (): void {
            $original = '<p>Hello World</p>';
            $rtf = FormatConverter::htmlToRtf($original);
            $html = FormatConverter::rtfToHtml($rtf);

            expect($html)->toContain('Hello World');
        });
    });
});

