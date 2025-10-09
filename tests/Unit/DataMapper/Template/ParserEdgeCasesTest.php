<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Template;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Template\FilterEngine;

describe('Parser Edge Cases', function(): void {
    beforeEach(function(): void {
        // These tests require safe mode for escape sequence handling
        FilterEngine::useFastSplit(false);
    });

    afterEach(function(): void {
        // Reset to default (fast mode)
        FilterEngine::useFastSplit(true);
    });
    describe('Escaped Quotes in Arguments', function(): void {
        it('handles escaped double quotes in double-quoted string', function(): void {
            $template = ['result' => '{{ data.value | default:"Say \"Hello\"" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Say "Hello"');
        });

        it('handles escaped single quotes in single-quoted string', function(): void {
            $template = ['result' => '{{ data.value | default:\'It\\\'s working\' }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe("It's working");
        });

        it('handles multiple escaped quotes', function(): void {
            $template = ['result' => '{{ data.value | default:"\"Quote\" and \"More\"" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('"Quote" and "More"');
        });

        // Note: Complex combinations of backslashes and quotes are covered by other tests
        // The basic escape sequences (\\, \", \', \n, \t, \r) work correctly
    });

    describe('Special Characters in Arguments', function(): void {
        it('handles pipe character in quoted argument', function(): void {
            $template = ['result' => '{{ data.tags | join:" | " }}'];
            $sources = ['data' => ['tags' => ['a', 'b', 'c']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('a | b | c');
        });

        it('handles colon in quoted argument', function(): void {
            $template = ['result' => '{{ data.value | default:"Time: 12:30:45" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Time: 12:30:45');
        });

        it('handles multiple pipes in quoted argument', function(): void {
            $template = ['result' => '{{ data.tags | join:" || " }}'];
            $sources = ['data' => ['tags' => ['x', 'y']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('x || y');
        });

        it('handles newline characters', function(): void {
            $template = ['result' => '{{ data.value | default:"Line1\nLine2" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe("Line1\nLine2");
        });

        it('handles tab characters', function(): void {
            $template = ['result' => '{{ data.value | default:"Col1\tCol2" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe("Col1\tCol2");
        });
    });

    describe('Empty and Whitespace Arguments', function(): void {
        it('handles empty string argument', function(): void {
            $template = ['result' => '{{ data.tags | join:"" }}'];
            $sources = ['data' => ['tags' => ['a', 'b', 'c']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('abc');
        });

        it('handles whitespace-only argument', function(): void {
            $template = ['result' => '{{ data.tags | join:"   " }}'];
            $sources = ['data' => ['tags' => ['a', 'b']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('a   b');
        });

        it('handles argument with leading/trailing spaces', function(): void {
            $template = ['result' => '{{ data.tags | join:"  -  " }}'];
            $sources = ['data' => ['tags' => ['x', 'y']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('x  -  y');
        });
    });

    describe('Multiple Filters with Complex Arguments', function(): void {
        it('chains filters with quoted arguments', function(): void {
            $template = ['result' => '{{ data.value | default:"N/A" | upper }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('N/A');
        });

        it('chains filters with pipe in quoted argument', function(): void {
            $template = ['result' => '{{ data.tags | join:" | " | upper }}'];
            $sources = ['data' => ['tags' => ['php', 'laravel']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('PHP | LARAVEL');
        });

        it('chains three filters with various arguments', function(): void {
            $template = ['result' => '{{ data.tags | join:", " | trim | upper }}'];
            $sources = ['data' => ['tags' => ['a', 'b', 'c']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('A, B, C');
        });
    });

    describe('Mixed Quote Types', function(): void {
        it('handles double quotes inside single-quoted argument', function(): void {
            $template = ['result' => '{{ data.value | default:\'Say "Hello"\' }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Say "Hello"');
        });

        it('handles single quotes inside double-quoted argument', function(): void {
            $template = ['result' => '{{ data.value | default:"It\'s working" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe("It's working");
        });
    });

    describe('Numeric Arguments', function(): void {
        it('handles integer arguments', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 150]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(100.0);
        });

        it('handles negative numbers', function(): void {
            $template = ['result' => '{{ data.value | between:-10:10 }}'];
            $sources = ['data' => ['value' => -20]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(-10.0);
        });

        it('handles decimal numbers', function(): void {
            $template = ['result' => '{{ data.value | between:0.5:1.5 }}'];
            $sources = ['data' => ['value' => 2.0]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(1.5);
        });

        it('handles zero', function(): void {
            $template = ['result' => '{{ data.value | default:0 }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('0');
        });
    });

    describe('Unicode and Special Characters', function(): void {
        it('handles unicode characters in arguments', function(): void {
            $template = ['result' => '{{ data.value | default:"Hello 世界 🌍" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Hello 世界 🌍');
        });

        it('handles emoji in join separator', function(): void {
            $template = ['result' => '{{ data.tags | join:" 🔹 " }}'];
            $sources = ['data' => ['tags' => ['A', 'B', 'C']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('A 🔹 B 🔹 C');
        });

        it('handles special HTML entities', function(): void {
            $template = ['result' => '{{ data.value | default:"&lt;tag&gt;" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('&lt;tag&gt;');
        });
    });

    describe('Edge Cases with Backslashes', function(): void {
        it('handles single backslash', function(): void {
            $template = ['result' => '{{ data.value | default:"Path\\File" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Path\File');
        });

        it('handles double backslash', function(): void {
            $template = ['result' => '{{ data.value | default:"C:\\\\Users" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('C:\\Users');
        });

        it('handles backslash at end', function(): void {
            // Template: "Path\\"
            // In template string: Path\\
            // After escape processing: Path\
            // Expected result: Path\ (single backslash)
            $template = ['result' => '{{ data.value | default:"Path\\\\" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            // PHP string 'Path\\' represents a single backslash after Path
            expect($result['result'])->toBe('Path\\');
        });
    });

    describe('Malformed Input Handling', function(): void {
        it('handles unclosed quotes gracefully', function(): void {
            $template = ['result' => '{{ data.value | default:"Unclosed }}'];
            $sources = ['data' => ['value' => null]];

            // Should not crash, behavior may vary
            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result)->toBeArray();
        });

        it('handles empty filter name', function(): void {
            $template = ['result' => '{{ data.value | }}'];
            $sources = ['data' => ['value' => 'test']];

            // Should not crash
            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result)->toBeArray();
        });

        it('handles multiple consecutive pipes', function(): void {
            $template = ['result' => '{{ data.value | trim || upper }}'];
            $sources = ['data' => ['value' => '  test  ']];

            // Should handle gracefully (empty filter name)
            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result)->toBeArray();
        });
    });
});

