<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

describe('Trim Transformer with Custom Characters', function(): void {
    describe('Default Behavior (Whitespace)', function(): void {
        it('trims whitespace in template expression', function(): void {
            $template = ['result' => '{{ data.value | trim }}'];
            $sources = ['data' => ['value' => '  hello  ']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('hello');
        });

        it('trims whitespace in map()', function(): void {
            $source = ['value' => '  hello  '];
            $mapping = ['result' => '{{ value | trim }}'];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['result'])->toBe('hello');
        });

        it('trims whitespace in pipeline mode', function(): void {
            $source = ['value' => '  hello  '];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new TrimStrings()])
                ->map()
                ->getTarget();

            expect($result['result'])->toBe('hello');
        });
    });

    describe('Custom Characters', function(): void {
        it('trims only dash characters', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => '- Sample - Swimming Pool -']];

            $result = DataMapper::source($sources)
                ->template($template)
                ->trimValues(false)
                ->map()
                ->getTarget();

            expect($result['result'])->toBe(' Sample - Swimming Pool ');
        });

        it('trims space and dash characters', function(): void {
            $template = ['result' => '{{ data.value | trim:" -" }}'];
            $sources = ['data' => ['value' => '- Sample - Swimming Pool -']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('Sample - Swimming Pool');
        });

        it('trims dash and space in different order', function(): void {
            $template = ['result' => '{{ data.value | trim:"- " }}'];
            $sources = ['data' => ['value' => '- Sample - Swimming Pool -']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('Sample - Swimming Pool');
        });

        it('trims only leading and trailing characters', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => '---Sample---']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('Sample');
        });

        it('does not trim characters in the middle', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => '---Sample-Text---']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('Sample-Text');
        });
    });

    describe('Pipeline Mode with Custom Characters', function(): void {
        it('trims custom characters in pipeline mode', function(): void {
            $source = ['value' => '- Sample - Swimming Pool -'];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new TrimStrings(' -')])
                ->map()
                ->getTarget();

            expect($result['result'])->toBe('Sample - Swimming Pool');
        });

        it('trims only dash in pipeline mode', function(): void {
            $source = ['value' => '---Sample---'];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new TrimStrings('-')])
                ->map()
                ->getTarget();

            expect($result['result'])->toBe('Sample');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles empty string', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => '']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('');
        });

        it('handles string with only trim characters', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => '---']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('');
        });

        it('handles non-string values', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => 123]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(123);
        });

        it('handles null values (field is skipped)', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" }}'];
            $sources = ['data' => ['value' => null]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            // Null values are skipped by default in mapFromTemplate
            expect($result)->not->toHaveKey('result');
        });
    });

    describe('Chaining with Other Transformers', function(): void {
        it('chains trim with upper', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" | upper }}'];
            $sources = ['data' => ['value' => '- sample -']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(' SAMPLE ');
        });

        it('chains trim with trim (different characters)', function(): void {
            $template = ['result' => '{{ data.value | trim:"-" | trim }}'];
            $sources = ['data' => ['value' => '- sample -']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('sample');
        });

        it('chains multiple transformers', function(): void {
            $template = ['result' => '{{ data.value | trim:" -" | upper | trim }}'];
            $sources = ['data' => ['value' => '  - sample -  ']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('SAMPLE');
        });
    });

    describe('Real-World Examples', function(): void {
        it('cleans XML description field', function(): void {
            $template = ['description' => '{{ data.description | trim:" -" }}'];
            $sources = ['data' => ['description' => '- Sample - Swimming Pool -']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['description'])->toBe('Sample - Swimming Pool');
        });

        it('cleans multiple fields with different trim characters', function(): void {
            $template = [
                'title' => '{{ data.title | trim:"*" }}',
                'description' => '{{ data.description | trim:" -" }}',
                'code' => '{{ data.code | trim:"_" }}',
            ];
            $sources = ['data' => [
                'title' => '***Important***',
                'description' => '- Sample - Swimming Pool -',
                'code' => '__CODE123__',
            ]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['title'])->toBe('Important');
            expect($result['description'])->toBe('Sample - Swimming Pool');
            expect($result['code'])->toBe('CODE123');
        });
    });
});

