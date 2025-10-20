<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('FluentDataMapper Entry Points', function(): void {
    describe('source() method', function(): void {
        it('accepts array data', function(): void {
            $source = ['name' => 'Alice'];

            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map()
                ->getTarget();

            expect($result)->toBe(['name' => 'Alice']);
        });

        it('auto-detects and loads JSON files', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_auto_detect.json';
            file_put_contents($tempFile, json_encode(['name' => 'Bob']));

            $result = DataMapper::source($tempFile)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map()
                ->getTarget();

            expect($result)->toBe(['name' => 'Bob']);

            unlink($tempFile);
        });

        it('accepts object data', function(): void {
            $source = (object)['name' => 'Charlie'];

            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map()
                ->getTarget();

            expect($result)->toBe(['name' => 'Charlie']);
        });
    });

    describe('sourceFile() method', function(): void {
        it('loads JSON files explicitly', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_explicit_file.json';
            file_put_contents($tempFile, json_encode(['name' => 'David']));

            $result = DataMapper::sourceFile($tempFile)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map()
                ->getTarget();

            expect($result)->toBe(['name' => 'David']);

            unlink($tempFile);
        });

        it('throws exception for non-existent files', function(): void {
            DataMapper::sourceFile('/non/existent/file.json')
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();
        })->throws(InvalidArgumentException::class, 'File not found');

        it('throws exception for invalid JSON', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_invalid_json.json';
            file_put_contents($tempFile, 'invalid json content');

            DataMapper::sourceFile($tempFile)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            unlink($tempFile);
        })->throws(InvalidArgumentException::class, 'Failed to parse JSON');
    });

    describe('template() entry point', function(): void {
        it('allows starting with template', function(): void {
            $result = DataMapper::template(['name' => '{{ name }}'])
                ->source(['name' => 'Eve'])
                ->target([])
                ->map()
                ->getTarget();

            expect($result)->toBe(['name' => 'Eve']);
        });

        it('works with complex templates', function(): void {
            $result = DataMapper::template([
                'user.name' => '{{ name }}',
                'user.age' => '{{ age }}',
            ])
                ->source(['name' => 'Frank', 'age' => 30])
                ->target([])
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Frank',
                    'age' => 30,
                ],
            ]);
        });
    });

    describe('target() entry point', function(): void {
        it('allows starting with target', function(): void {
            $result = DataMapper::target([])
                ->source(['name' => 'Grace'])
                ->template(['name' => '{{ name }}'])
                ->map()
                ->getTarget();

            expect($result)->toBe(['name' => 'Grace']);
        });

        it('works with object targets', function(): void {
            $target = new stdClass();

            $result = DataMapper::target($target)
                ->source(['name' => 'Henry'])
                ->template(['name' => '{{ name }}'])
                ->map()
                ->getTarget();

            expect($result)->toBeInstanceOf(stdClass::class);
            assert($result instanceof stdClass);
            expect($result->name)->toBe('Henry');
        });
    });

    describe('Flexible entry point combinations', function(): void {
        it('allows any order of configuration', function(): void {
            $result1 = DataMapper::source(['name' => 'Leo'])
                ->template(['name' => '{{ name }}'])
                ->target([])
                ->map()
                ->getTarget();

            $result2 = DataMapper::template(['name' => '{{ name }}'])
                ->source(['name' => 'Leo'])
                ->target([])
                ->map()
                ->getTarget();

            $result3 = DataMapper::target([])
                ->template(['name' => '{{ name }}'])
                ->source(['name' => 'Leo'])
                ->map()
                ->getTarget();

            expect($result1)->toBe(['name' => 'Leo']);
            expect($result2)->toBe(['name' => 'Leo']);
            expect($result3)->toBe(['name' => 'Leo']);
        });

        it('works with all fluent methods', function(): void {
            $result = DataMapper::source(['items' => [['name' => 'A'], ['name' => 'B']]])
                ->target([])
                ->template(['names' => '{{ items.*.name }}'])
                ->reindexWildcard(true)
                ->map()
                ->getTarget();

            expect($result)->toBe(['names' => ['A', 'B']]);
        });
    });
});

