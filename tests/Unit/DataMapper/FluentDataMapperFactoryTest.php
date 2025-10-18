<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;

describe('FluentDataMapper Factory Methods', function(): void {
    describe('fromSource()', function(): void {
        it('creates mapper from array', function(): void {
            $source = ['name' => 'John', 'age' => 30];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from object', function(): void {
            $source = (object)['name' => 'John', 'age' => 30];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from stdClass', function(): void {
            $source = new stdClass();
            $source->name = 'John';
            $source->age = 30;

            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from JSON string', function(): void {
            $source = '{"name":"John","age":30}';
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from empty array', function(): void {
            $source = [];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from null', function(): void {
            $source = null;
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from nested array', function(): void {
            $source = [
                'user' => [
                    'name' => 'John',
                    'address' => [
                        'city' => 'Berlin',
                        'zip' => '10115',
                    ],
                ],
            ];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from array with numeric keys', function(): void {
            $source = [0 => 'first', 1 => 'second', 2 => 'third'];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from mixed array', function(): void {
            $source = ['name' => 'John', 0 => 'first', 'age' => 30];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('from()', function(): void {
        it('creates mapper from array', function(): void {
            $source = ['name' => 'John'];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from object', function(): void {
            $source = (object)['name' => 'John'];
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('creates mapper from JSON string', function(): void {
            $source = '{"name":"John"}';
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('auto-detects file path and uses fromFile()', function(): void {
            // Create temp file
            $tempFile = sys_get_temp_dir() . '/test_' . uniqid() . '.json';
            file_put_contents($tempFile, '{"name":"John"}');

            $mapper = DataMapper::source($tempFile);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);

            // Cleanup
            unlink($tempFile);
        });

        it('uses fromSource() for non-existent file path', function(): void {
            $source = '/non/existent/file.json';
            $mapper = DataMapper::source($source);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('fromFile()', function(): void {
        it('creates mapper from JSON file', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_' . uniqid() . '.json';
            file_put_contents($tempFile, '{"name":"John","age":30}');

            $mapper = DataMapper::sourceFile($tempFile);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);

            unlink($tempFile);
        });

        it('creates mapper from empty JSON file', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_' . uniqid() . '.json';
            file_put_contents($tempFile, '{}');

            $mapper = DataMapper::sourceFile($tempFile);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);

            unlink($tempFile);
        });

        it('creates mapper from JSON array file', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_' . uniqid() . '.json';
            file_put_contents($tempFile, '[{"name":"John"},{"name":"Jane"}]');

            $mapper = DataMapper::sourceFile($tempFile);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);

            unlink($tempFile);
        });
    });

    describe('Factory method chaining', function(): void {
        it('can chain methods after fromSource()', function(): void {
            $source = ['name' => 'John'];
            $mapper = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}']);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can chain methods after from()', function(): void {
            $source = ['name' => 'John'];
            $mapper = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}']);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can chain methods after fromFile()', function(): void {
            $tempFile = sys_get_temp_dir() . '/test_' . uniqid() . '.json';
            file_put_contents($tempFile, '{"name":"John"}');

            $mapper = DataMapper::sourceFile($tempFile)
                ->target([])
                ->template(['name' => '{{ name }}']);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);

            unlink($tempFile);
        });
    });
});
