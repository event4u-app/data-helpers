<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;

/**
 * Comprehensive tests for filter support across ALL mapping methods.
 *
 * This test suite ensures that filters work consistently in:
 * - mapFromFile()
 * - map()
 * - mapFromTemplate()
 *
 * Tests cover:
 * - Built-in filters
 * - Custom filters
 * - Filter chains
 * - Filters with wildcards
 * - Filters with default values
 * - Filters with pipeline transformers
 */
describe('Filter Support Across All Mapping Methods', function(): void {
    beforeEach(function(): void {
        // Clear transformer registry before each test
        TransformerRegistry::clear();
    });

    afterEach(function(): void {
        TransformerRegistry::clear();
    });

    describe('mapFromFile() - Filter Support', function(): void {
        it('applies single filter to simple field', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'email' => '{{ company.email | lower }}',
            ];

            $result = DataMapper::mapFromFile($jsonFile, [], $mapping);

            expect($result['email'])->toBe('info@techcorp.example');
        });

        it('applies filter chain to simple field', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'name' => '{{ company.name | lower | ucfirst }}',
            ];

            $result = DataMapper::mapFromFile($jsonFile, [], $mapping);

            expect($result['name'])->toBe('Techcorp solutions');
        });

        it('applies filter to wildcard field', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'dept_names' => '{{ company.departments.*.name | upper }}',
            ];

            $result = DataMapper::mapFromFile($jsonFile, [], $mapping);

            expect($result['dept_names'])->toBeArray();
            expect($result['dept_names'][0])->toBe('ENGINEERING');
            expect($result['dept_names'][1])->toBe('SALES');
            expect($result['dept_names'][2])->toBe('HUMAN RESOURCES');
        });

        it('applies filter chain to wildcard field', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'dept_codes' => '{{ company.departments.*.code | lower | ucfirst }}',
            ];

            $result = DataMapper::mapFromFile($jsonFile, [], $mapping);

            expect($result['dept_codes'])->toBeArray();
            expect($result['dept_codes'][0])->toBe('Eng');
            expect($result['dept_codes'][1])->toBe('Sal');
            expect($result['dept_codes'][2])->toBe('Hr');
        });

        it('applies filter with default value', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'missing' => '{{ company.missing_field | default:"N/A" | upper }}',
            ];

            $result = DataMapper::mapFromFile($jsonFile, [], $mapping);

            expect($result['missing'])->toBe('N/A');
        });

        it('applies decode_html filter', function(): void {
            // Create temp file with HTML entities
            $tempFile = sys_get_temp_dir() . '/test_html_entities.json';
            file_put_contents($tempFile, json_encode([
                'text' => 'Herbert&#32;Meier',
                'description' => 'Sample&amp;#32;&amp;#45;&amp;#32;Pool',
            ]));

            $mapping = [
                'text' => '{{ text | decode_html }}',
                'description' => '{{ description | decode_html }}',
            ];

            $result = DataMapper::mapFromFile($tempFile, [], $mapping);

            expect($result['text'])->toBe('Herbert Meier');
            expect($result['description'])->toBe('Sample - Pool');

            unlink($tempFile);
        });

        it('combines filters with pipeline transformers', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $mapping = [
                'email' => '{{ company.email | lower }}',
            ];

            $result = DataMapper::pipe([new TrimStrings()])
                ->mapFromFile($jsonFile, [], $mapping);

            expect($result['email'])->toBe('info@techcorp.example');
        });
    });

    describe('map() - Filter Support', function(): void {
        it('applies single filter to simple field', function(): void {
            $source = ['email' => 'ALICE@EXAMPLE.COM'];

            $mapping = [
                'email' => '{{ email | lower }}',
            ];

            $result = DataMapper::map($source, [], $mapping);

            expect($result['email'])->toBe('alice@example.com');
        });

        it('applies filter chain to simple field', function(): void {
            $source = ['name' => 'ALICE SMITH'];

            $mapping = [
                'name' => '{{ name | lower | ucwords }}',
            ];

            $result = DataMapper::map($source, [], $mapping);

            expect($result['name'])->toBe('Alice Smith');
        });

        it('applies filter to wildcard field', function(): void {
            $source = [
                'users' => [
                    ['name' => 'alice'],
                    ['name' => 'bob'],
                    ['name' => 'charlie'],
                ],
            ];

            $mapping = [
                'names' => '{{ users.*.name | ucfirst }}',
            ];

            $result = DataMapper::map($source, [], $mapping);

            expect($result['names'])->toBeArray();
            expect($result['names'][0])->toBe('Alice');
            expect($result['names'][1])->toBe('Bob');
            expect($result['names'][2])->toBe('Charlie');
        });

        it('applies filter chain to wildcard field', function(): void {
            $source = [
                'users' => [
                    ['email' => 'ALICE@EXAMPLE.COM'],
                    ['email' => 'BOB@EXAMPLE.COM'],
                ],
            ];

            $mapping = [
                'emails' => '{{ users.*.email | lower | trim }}',
            ];

            $result = DataMapper::map($source, [], $mapping);

            expect($result['emails'])->toBeArray();
            expect($result['emails'][0])->toBe('alice@example.com');
            expect($result['emails'][1])->toBe('bob@example.com');
        });

        it('applies filter with default value', function(): void {
            $source = ['name' => null];

            $mapping = [
                'name' => '{{ name | default:"Unknown" | upper }}',
            ];

            $result = DataMapper::map($source, [], $mapping);

            expect($result['name'])->toBe('UNKNOWN');
        });

        it('applies decode_html filter', function(): void {
            $source = [
                'text' => 'Herbert&#32;Meier',
                'description' => 'Sample&amp;#32;&amp;#45;&amp;#32;Pool',
            ];

            $mapping = [
                'text' => '{{ text | decode_html }}',
                'description' => '{{ description | decode_html }}',
            ];

            $result = DataMapper::map($source, [], $mapping);

            expect($result['text'])->toBe('Herbert Meier');
            expect($result['description'])->toBe('Sample - Pool');
        });

        it('combines filters with pipeline transformers', function(): void {
            $source = ['email' => '  ALICE@EXAMPLE.COM  '];

            $mapping = [
                'email' => '{{ email | lower }}',
            ];

            $result = DataMapper::pipe([new TrimStrings()])
                ->map($source, [], $mapping);

            expect($result['email'])->toBe('alice@example.com');
        });
    });

    describe('mapFromTemplate() - Filter Support (Baseline)', function(): void {
        it('applies single filter to simple field', function(): void {
            $template = ['email' => '{{ user.email | lower }}'];
            $sources = ['user' => ['email' => 'ALICE@EXAMPLE.COM']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['email'])->toBe('alice@example.com');
        });

        it('applies filter chain to simple field', function(): void {
            $template = ['name' => '{{ user.name | lower | ucfirst }}'];
            $sources = ['user' => ['name' => 'ALICE']];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['name'])->toBe('Alice');
        });

        it('applies filter to wildcard field', function(): void {
            $template = ['names' => '{{ users | keys }}'];
            $sources = [
                'users' => [
                    'alice' => 1,
                    'bob' => 2,
                ],
            ];

            $result = DataMapper::mapFromTemplate($template, $sources);

            expect($result['names'])->toBeArray();
            expect($result['names'][0])->toBe('alice');
            expect($result['names'][1])->toBe('bob');
        });
    });
});

