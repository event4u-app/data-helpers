<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\DecodeHtmlEntities;

describe('DecodeHtmlEntities Transformer', function(): void {
    it('decodes numeric HTML entities', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Sample&#32;&#45;&#32;Swimming&#32;Pool',
        ]);

        expect($result['result'])->toBe('Sample - Swimming Pool');
    });

    it('decodes double-encoded numeric entities', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        // This is what we see in version3.xml: &amp;#32; (double-encoded)
        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Sample&amp;#32;&amp;#45;&amp;#32;Swimming&amp;#32;Pool&amp;#32;&amp;#45;',
        ]);

        expect($result['result'])->toBe('Sample - Swimming Pool -');
    });

    it('decodes named HTML entities', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => '&lt;div&gt;Hello &amp; Goodbye&lt;/div&gt;',
        ]);

        expect($result['result'])->toBe('<div>Hello & Goodbye</div>');
    });

    it('decodes quotes', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Say &quot;Hello&quot; and &apos;Goodbye&apos;',
        ]);

        expect($result['result'])->toBe('Say "Hello" and \'Goodbye\'');
    });

    it('handles mixed entities', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Price:&#32;&euro;100&#32;&amp;&#32;Tax:&#32;20%',
        ]);

        expect($result['result'])->toBe('Price: €100 & Tax: 20%');
    });

    it('preserves already decoded text', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Normal text with spaces',
        ]);

        expect($result['result'])->toBe('Normal text with spaces');
    });

    it('handles empty strings', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, ['value' => '']);

        expect($result['result'])->toBe('');
    });

    it('handles non-string values', function(): void {
        $template = [
            'number' => '{{ number | decode_html }}',
            'null' => '{{ null_value | decode_html }}',
            'array' => '{{ array_value | decode_html }}',
        ];

        $result = DataMapper::mapFromTemplate($template, [
            'number' => 123,
            'null_value' => null,
            'array_value' => ['a', 'b'],
        ]);

        expect($result['number'])->toBe(123);
        expect($result['null'])->toBeNull();
        expect($result['array'])->toBe(['a', 'b']);
    });

    it('works with html_decode alias', function(): void {
        $template = ['result' => '{{ value | html_decode }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Test&#32;&amp;&#32;Demo',
        ]);

        expect($result['result'])->toBe('Test & Demo');
    });

    it('works with decode_entities alias', function(): void {
        $template = ['result' => '{{ value | decode_entities }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => '&lt;tag&gt;',
        ]);

        expect($result['result'])->toBe('<tag>');
    });

    it('handles triple-encoded entities', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        // Triple-encoded: &amp;amp;#32; -> &amp;#32; -> &#32; -> space
        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Test&amp;amp;#32;Value',
        ]);

        expect($result['result'])->toBe('Test Value');
    });

    it('can be chained with other transformers', function(): void {
        $template = ['result' => '{{ value | decode_html | trim | ucfirst }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => '&#32;&#32;hello&#32;world&#32;&#32;',
        ]);

        expect($result['result'])->toBe('Hello world');
    });

    it('handles unicode entities', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => '&#128512;&#32;Smile&#32;&#128077;&#32;Thumbs&#32;up',
        ]);

        expect($result['result'])->toBe('😀 Smile 👍 Thumbs up');
    });

    it('handles real-world version3 XML data', function(): void {
        $template = ['result' => '{{ value | decode_html }}'];

        // Real example from version3.xml
        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'Sample&amp;#32;&amp;#45;&amp;#32;Swimming&amp;#32;Pool&amp;#32;&amp;#45;',
        ]);

        expect($result['result'])->toBe('Sample - Swimming Pool -');
    });
});

