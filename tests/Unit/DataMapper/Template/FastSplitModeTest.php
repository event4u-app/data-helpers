<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Template\FilterEngine;

beforeEach(function(): void {
    // Reset to fast mode (default) before each test
    FilterEngine::useFastSplit(true);
});

afterEach(function(): void {
    // Reset to fast mode (default) after each test
    FilterEngine::useFastSplit(true);
});

describe('Fast Split Mode', function(): void {
    it('fast split mode is enabled by default', function(): void {
        // Reset to default
        FilterEngine::useFastSplit(true);
        expect(FilterEngine::isFastSplitEnabled())->toBeTrue();

        FilterEngine::useFastSplit(false);
        expect(FilterEngine::isFastSplitEnabled())->toBeFalse();

        FilterEngine::useFastSplit(true);
        expect(FilterEngine::isFastSplitEnabled())->toBeTrue();
    });

    it('works with simple cases in fast mode (default)', function(): void {
        // Fast mode is default, no need to set it
        $template = ['result' => '{{ data.value | trim }}'];
        $sources = ['data' => ['value' => '  hello  ']];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result)->toBe(['result' => 'hello']);
    });

    it('works with quoted strings in fast mode (default)', function(): void {
        // Fast mode is default
        $template = ['result' => '{{ data.tags | join:" | " }}'];
        $sources = ['data' => ['tags' => ['a', 'b', 'c']]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result)->toBe(['result' => 'a | b | c']);
    });

    it('works with simple default values in fast mode (default)', function(): void {
        // Fast mode is default
        $template = ['result' => '{{ data.value | default:"Unknown" }}'];
        $sources = ['data' => ['value' => null]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['result'])->toBe('Unknown');
    });

    it('works with numeric arguments in fast mode (default)', function(): void {
        // Fast mode is default
        $template = ['result' => '{{ data.value | between:0:100 }}'];
        $sources = ['data' => ['value' => 50]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result)->toBe(['result' => 50.0]);
    });

    it('processes escape sequences in safe mode (opt-in)', function(): void {
        // Enable safe mode explicitly
        FilterEngine::useFastSplit(false);

        $template = ['result' => '{{ data.value | default:"Line1\nLine2" }}'];
        $sources = ['data' => ['value' => null]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        // Safe mode: \n is converted to actual newline
        expect(is_string($result['result']) && str_contains($result['result'], "\n"))->toBeTrue();
        expect($result['result'])->toBe("Line1\nLine2");
    });

    it('processes escaped quotes in safe mode (opt-in)', function(): void {
        // Enable safe mode explicitly
        FilterEngine::useFastSplit(false);

        $template = ['result' => '{{ data.value | default:"Say \"Hello\"" }}'];
        $sources = ['data' => ['value' => null]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        // Safe mode: \" is converted to "
        expect($result['result'])->toBe('Say "Hello"');
    });

    it('handles tabs in safe mode', function(): void {
        FilterEngine::useFastSplit(false);

        $template = ['result' => '{{ data.value | default:"Col1\tCol2" }}'];
        $sources = ['data' => ['value' => null]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect(is_string($result['result']) && str_contains($result['result'], "\t"))->toBeTrue();
        expect($result['result'])->toBe("Col1\tCol2");
    });

    it('handles backslashes in safe mode', function(): void {
        FilterEngine::useFastSplit(false);

        $template = ['result' => '{{ data.value | default:"Path\\\\File" }}'];
        $sources = ['data' => ['value' => null]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        // Safe mode: \\ becomes \
        expect($result['result'])->toBe('Path\\File');
    });

    it('works with multiple filters in fast mode (default)', function(): void {
        // Fast mode is default
        $template = ['result' => '{{ data.tags | join:" | " | upper }}'];
        $sources = ['data' => ['tags' => ['a', 'b', 'c']]];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result)->toBe(['result' => 'A | B | C']);
    });

    it('works with complex expressions in fast mode (default)', function(): void {
        // Fast mode is default

        $template = [
            'name' => '{{ user.name | trim | upper }}',
            'age' => '{{ user.age | between:18:65 }}',
            'tags' => '{{ user.tags | join:", " }}',
        ];

        $sources = [
            'user' => [
                'name' => '  john  ',
                'age' => 25,
                'tags' => ['php', 'laravel', 'vue'],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result)->toBe([
            'name' => 'JOHN',
            'age' => 25.0,
            'tags' => 'php, laravel, vue',
        ]);
    });

    it('can switch between modes during runtime', function(): void {
        $template = ['result' => '{{ data.value | default:"Line1\nLine2" }}'];
        $sources = ['data' => ['value' => null]];

        // Fast mode (default) - does NOT process escapes
        FilterEngine::useFastSplit(true);
        $result1 = DataMapper::mapFromTemplate($template, $sources);
        expect($result1['result'])->not->toBeEmpty();

        // Safe mode - processes escapes
        FilterEngine::useFastSplit(false);
        $result2 = DataMapper::mapFromTemplate($template, $sources);
        expect(is_string($result2['result']) && str_contains($result2['result'], "\n"))->toBeTrue();

        // Back to fast mode (default)
        FilterEngine::useFastSplit(true);
        $result3 = DataMapper::mapFromTemplate($template, $sources);
        expect($result3['result'])->not->toBeEmpty();
    });
});
