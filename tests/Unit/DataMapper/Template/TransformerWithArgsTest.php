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
        it('clamps value to maximum', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 150]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(100.0);
        });

        it('clamps value to minimum', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => -50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(0.0);
        });

        it('returns value when within range', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(50.0);
        });

        it('works with negative ranges', function(): void {
            $template = ['result' => '{{ data.value | between:-10:10 }}'];
            $sources = ['data' => ['value' => -15]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(-10.0);
        });

        it('works with decimal values', function(): void {
            $template = ['result' => '{{ data.value | between:0:1 }}'];
            $sources = ['data' => ['value' => 0.75]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(0.75);
        });

        it('returns non-numeric value unchanged', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 }}'];
            $sources = ['data' => ['value' => 'not-a-number']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe('not-a-number');
        });

        it('returns value unchanged when insufficient arguments', function(): void {
            $template = ['result' => '{{ data.value | between:0 }}'];
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

        it('chains between and default transformers', function(): void {
            $template = ['result' => '{{ data.value | between:0:100 | default:"N/A" }}'];
            $sources = ['data' => ['value' => 50]];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['result'])->toBe(50.0);
        });
    });
});

