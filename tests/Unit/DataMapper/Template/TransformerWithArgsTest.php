<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Transformers with Arguments', function(): void {
    describe('DefaultValue Transformer', function(): void {
        it('returns empty string when value is null and no argument provided', function(): void {
            $template = ['result' => '{{ data.value | default }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('');
        });

        it('returns empty string when value is blank and no argument provided', function(): void {
            $template = ['result' => '{{ data.value | default }}'];
            $sources = ['data' => ['value' => '']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('');
        });

        it('returns value when not null or blank', function(): void {
            $template = ['result' => '{{ data.value | default }}'];
            $sources = ['data' => ['value' => 'Hello']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Hello');
        });

        it('returns custom default string when value is null', function(): void {
            $template = ['result' => '{{ data.value | default:"Unknown" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Unknown');
        });

        it('returns custom default string when value is blank', function(): void {
            $template = ['result' => '{{ data.value | default:"Unknown" }}'];
            $sources = ['data' => ['value' => '']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('Unknown');
        });

        it('returns string "0" when specified as default', function(): void {
            $template = ['result' => '{{ data.value | default:"0" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('0');
        });

        it('returns numeric 0 when specified as default', function(): void {
            $template = ['result' => '{{ data.value | default:0 }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('0');
        });

        it('returns value when value is 0 (not null)', function(): void {
            $template = ['result' => '{{ data.value | default:"Unknown" }}'];
            $sources = ['data' => ['value' => 0]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(0);
        });
    });

    describe('Join Transformer', function(): void {
        it('joins array with default separator (comma space)', function(): void {
            $template = ['result' => '{{ data.tags | join }}'];
            $sources = ['data' => ['tags' => ['php', 'laravel', 'testing']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('php, laravel, testing');
        });

        it('joins array with custom separator', function(): void {
            $template = ['result' => '{{ data.tags | join:" | " }}'];
            $sources = ['data' => ['tags' => ['php', 'laravel', 'testing']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('php | laravel | testing');
        });

        it('joins array with no separator', function(): void {
            $template = ['result' => '{{ data.tags | join:"" }}'];
            $sources = ['data' => ['tags' => ['a', 'b', 'c']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('abc');
        });

        it('joins array with comma separator', function(): void {
            $template = ['result' => '{{ data.tags | join:"," }}'];
            $sources = ['data' => ['tags' => ['one', 'two', 'three']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('one,two,three');
        });

        it('returns non-array value unchanged', function(): void {
            $template = ['result' => '{{ data.value | join }}'];
            $sources = ['data' => ['value' => 'not-an-array']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('not-an-array');
        });
    });

    describe('Between Transformer', function(): void {
        it('returns true when value is within range (inclusive)', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBeTrue();
        });

        it('returns false when value exceeds maximum', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 150]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBeFalse();
        });

        it('returns false when value is below minimum', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => -50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBeFalse();
        });

        it('returns true for boundary values (inclusive)', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];

            $result1 = DataMapper::mapFromTemplate($template, ['data' => ['value' => 0]]);
            $result2 = DataMapper::mapFromTemplate($template, ['data' => ['value' => 100]]);

            expect($result1['result'])->toBeTrue();
            expect($result2['result'])->toBeTrue();
        });

        it('works with strict mode (exclusive boundaries)', function(): void {
            $template = ['result' => '{{ data.value | between:0:100:strict }}'];

            $result1 = DataMapper::mapFromTemplate($template, ['data' => ['value' => 0]]);
            $result2 = DataMapper::mapFromTemplate($template, ['data' => ['value' => 50]]);
            $result3 = DataMapper::mapFromTemplate($template, ['data' => ['value' => 100]]);

            expect($result1['result'])->toBeFalse(); // 0 is not > 0
            expect($result2['result'])->toBeTrue();  // 50 is > 0 and < 100
            expect($result3['result'])->toBeFalse(); // 100 is not < 100
        });

        it('works with negative ranges', function(): void {
            $template = ['result' => '{{ data.value | between:-10:10 }}'];

            $result1 = DataMapper::mapFromTemplate($template, ['data' => ['value' => -15]]);
            $result2 = DataMapper::mapFromTemplate($template, ['data' => ['value' => 0]]);

            expect($result1['result'])->toBeFalse();
            expect($result2['result'])->toBeTrue();
        });

        it('works with decimal values', function(): void {
            $template = ['result' => '{{ data.value | between:0:1 }}'];
            $sources = ['data' => ['value' => 0.75]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBeTrue();
        });

        it('returns false for non-numeric values', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 'not-a-number']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBeFalse();
        });

        it('returns false when insufficient arguments', function(): void {
            $template = ['result' => '{{ data.value | between:0 }}'];
            $sources = ['data' => ['value' => 150]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBeFalse();
        });
    });

    describe('Clamp Transformer', function(): void {
        it('clamps value to maximum', function(): void {
            $template = ['result' => '{{ data.value | clamp:0:100 }}'];
            $sources = ['data' => ['value' => 150]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(100.0);
        });

        it('clamps value to minimum', function(): void {
            $template = ['result' => '{{ data.value | clamp:0:100 }}'];
            $sources = ['data' => ['value' => -50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(0.0);
        });

        it('returns value when within range', function(): void {
            $template = ['result' => '{{ data.value | clamp:0:100 }}'];
            $sources = ['data' => ['value' => 50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(50.0);
        });

        it('works with negative ranges', function(): void {
            $template = ['result' => '{{ data.value | clamp:-10:10 }}'];
            $sources = ['data' => ['value' => -15]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(-10.0);
        });

        it('works with decimal values', function(): void {
            $template = ['result' => '{{ data.value | clamp:0:1 }}'];
            $sources = ['data' => ['value' => 0.75]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(0.75);
        });

        it('returns non-numeric value unchanged', function(): void {
            $template = ['result' => '{{ data.value | clamp:0:100 }}'];
            $sources = ['data' => ['value' => 'not-a-number']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('not-a-number');
        });

        it('returns value unchanged when insufficient arguments', function(): void {
            $template = ['result' => '{{ data.value | clamp:0 }}'];
            $sources = ['data' => ['value' => 150]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(150);
        });
    });

    describe('Chaining Transformers with Arguments', function(): void {
        it('chains default and upper transformers', function(): void {
            $template = ['result' => '{{ data.value | default:"unknown" | upper }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('UNKNOWN');
        });

        it('chains join and trim transformers', function(): void {
            $template = ['result' => '{{ data.tags | join:" - " | trim }}'];
            $sources = ['data' => ['tags' => ['php', 'laravel']]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('php - laravel');
        });

        it('chains clamp and default transformers', function(): void {
            $template = ['result' => '{{ data.value | clamp:0:100 | default:"N/A" }}'];
            $sources = ['data' => ['value' => 50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(50.0);
        });
    });
});

