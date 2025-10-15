<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;
use Tests\utils\Filters\AlternatingCase;
use Tests\utils\Filters\ReverseString;

beforeEach(function(): void {
    FilterRegistry::clear();
    FilterRegistry::register(AlternatingCase::class);
    FilterRegistry::register(ReverseString::class);
});

afterEach(function(): void {
    FilterRegistry::clear();
});

/**
 * Comprehensive tests for CUSTOM filter support across ALL mapping methods.
 *
 * This ensures custom filters work consistently in:
 * - mapFromFile()
 * - map()
 * - mapFromTemplate()
 */
describe('Custom Filter Support Across All Mapping Methods', function(): void {
    describe('mapFromFile() - Custom Filter Support', function(): void {
        it('applies custom filter to simple field', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_custom_filter.json';
            file_put_contents($tempFile, json_encode([
                'name' => 'hello world',
            ]));

            $mapping = [
                'name' => '{{ name | alternating }}',
            ];

            $result = DataMapper::mapFromFile($tempFile, [], $mapping);

            expect($result['name'])->toBe('hElLo wOrLd');

            unlink($tempFile);
        });

        it('chains custom filters', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_custom_chain.json';
            file_put_contents($tempFile, json_encode([
                'text' => 'hello',
            ]));

            $mapping = [
                'text' => '{{ text | alternating | reverse_str }}',
            ];

            $result = DataMapper::mapFromFile($tempFile, [], $mapping);

            // 'hello' -> 'hElLo' -> 'oLlEh'
            expect($result['text'])->toBe('oLlEh');

            unlink($tempFile);
        });

        it('chains custom and built-in filters', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_mixed_chain.json';
            file_put_contents($tempFile, json_encode([
                'text' => 'hello',
            ]));

            $mapping = [
                'text' => '{{ text | upper | alternating }}',
            ];

            $result = DataMapper::mapFromFile($tempFile, [], $mapping);

            // 'hello' -> 'HELLO' -> 'hElLo'
            expect($result['text'])->toBe('hElLo');

            unlink($tempFile);
        });

        it('applies custom filter to wildcard field', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_custom_wildcard.json';
            file_put_contents($tempFile, json_encode([
                'items' => [
                    ['name' => 'alice'],
                    ['name' => 'bob'],
                    ['name' => 'charlie'],
                ],
            ]));

            $mapping = [
                'names' => '{{ items.*.name | alternating }}',
            ];

            $result = DataMapper::mapFromFile($tempFile, [], $mapping);

            expect($result['names'])->toBeArray();
            expect($result['names'][0])->toBe('aLiCe');
            expect($result['names'][1])->toBe('bOb');
            expect($result['names'][2])->toBe('cHaRlIe');

            unlink($tempFile);
        });

        it('applies custom filter chain to wildcard field', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_custom_wildcard_chain.json';
            file_put_contents($tempFile, json_encode([
                'items' => [
                    ['text' => 'hello'],
                    ['text' => 'world'],
                ],
            ]));

            $mapping = [
                'texts' => '{{ items.*.text | upper | alternating | reverse_str }}',
            ];

            $result = DataMapper::mapFromFile($tempFile, [], $mapping);

            expect($result['texts'])->toBeArray();
            // 'hello' -> 'HELLO' -> 'hElLo' -> 'oLlEh'
            expect($result['texts'][0])->toBe('oLlEh');
            // 'world' -> 'WORLD' -> 'wOrLd' -> 'dLrOw'
            expect($result['texts'][1])->toBe('dLrOw');

            unlink($tempFile);
        });
    });

    describe('map() - Custom Filter Support', function(): void {
        it('applies custom filter to simple field', function(): void {
            $source = ['name' => 'hello world'];

            $mapping = [
                'name' => '{{ name | alternating }}',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['name'])->toBe('hElLo wOrLd');
        });

        it('chains custom filters', function(): void {
            $source = ['text' => 'hello'];

            $mapping = [
                'text' => '{{ text | alternating | reverse_str }}',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['text'])->toBe('oLlEh');
        });

        it('chains custom and built-in filters', function(): void {
            $source = ['text' => 'hello'];

            $mapping = [
                'text' => '{{ text | upper | alternating }}',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['text'])->toBe('hElLo');
        });

        it('applies custom filter to wildcard field', function(): void {
            $source = [
                'items' => [
                    ['name' => 'alice'],
                    ['name' => 'bob'],
                ],
            ];

            $mapping = [
                'names' => '{{ items.*.name | alternating }}',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['names'])->toBeArray();
            expect($result['names'][0])->toBe('aLiCe');
            expect($result['names'][1])->toBe('bOb');
        });

        it('applies custom filter chain to wildcard field', function(): void {
            $source = [
                'items' => [
                    ['text' => 'hello'],
                    ['text' => 'world'],
                ],
            ];

            $mapping = [
                'texts' => '{{ items.*.text | upper | alternating | reverse_str }}',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['texts'])->toBeArray();
            expect($result['texts'][0])->toBe('oLlEh');
            expect($result['texts'][1])->toBe('dLrOw');
        });

        it('applies custom filter with default value', function(): void {
            $source = ['name' => null];

            $mapping = [
                'name' => '{{ name | default:"Unknown" | alternating }}',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result['name'])->toBe('uNkNoWn');
        });
    });

    describe('mapFromTemplate() - Custom Filter Support (Baseline)', function(): void {
        it('applies custom filter to simple field', function(): void {
            $template = ['name' => '{{ user.name | alternating }}'];
            $sources = ['user' => ['name' => 'hello world']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['name'])->toBe('hElLo wOrLd');
        });

        it('chains custom filters', function(): void {
            $template = ['text' => '{{ user.text | alternating | reverse_str }}'];
            $sources = ['user' => ['text' => 'hello']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['text'])->toBe('oLlEh');
        });

        it('applies custom filter with array value', function(): void {
            $template = ['keys' => '{{ users | keys }}'];
            $sources = [
                'users' => [
                    'alice' => 1,
                    'bob' => 2,
                ],
            ];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['keys'])->toBeArray();
            expect($result['keys'][0])->toBe('alice');
            expect($result['keys'][1])->toBe('bob');
        });
    });

    describe('Cross-Method Consistency', function(): void {
        it('produces identical results for map() and mapFromFile()', function(): void {
            // Prepare data
            $source = [
                'items' => [
                    ['text' => 'hello'],
                    ['text' => 'world'],
                ],
            ];

            $tempFile = sys_get_temp_dir() . '/test_consistency.json';
            file_put_contents($tempFile, json_encode($source));

            $mapping = [
                'texts' => '{{ items.*.text | upper | alternating }}',
            ];

            // Test map() and mapFromFile()
            $resultMap = DataMapper::source($source)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();
            $resultFile = DataMapper::mapFromFile($tempFile, [], $mapping);

            // Both should produce identical results
            expect($resultMap['texts'])->toEqual($resultFile['texts']);
            expect($resultMap['texts'][0])->toBe('hElLo');
            expect($resultMap['texts'][1])->toBe('wOrLd');

            unlink($tempFile);
        });
    });
});

