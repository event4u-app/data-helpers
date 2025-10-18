<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperResult;
use event4u\DataHelpers\Exceptions\ConversionException;

describe('DataMapperResult', function(): void {
    describe('getTarget()', function(): void {
        it('returns the target', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeArray();
            expect($result->getTarget())->toHaveKey('name');
        });

        it('returns array target', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeArray();
        });

        it('returns object target', function(): void {
            $target = new stdClass();
            $result = DataMapper::source(['name' => 'John'])
                ->target($target)
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeObject();
        });

        it('returns empty array for empty mapping', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTarget())->toBe([]);
        });
    });

    describe('getSource()', function(): void {
        it('returns the original source', function(): void {
            $source = ['name' => 'John', 'age' => 30];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getSource())->toBe($source);
        });

        it('returns array source', function(): void {
            $source = ['name' => 'John'];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getSource())->toBeArray();
        });

        it('returns object source', function(): void {
            $source = (object)['name' => 'John'];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getSource())->toBeObject();
        });
    });

    describe('getTemplate()', function(): void {
        it('returns the template', function(): void {
            $template = ['name' => '{{ name }}', 'age' => '{{ age }}'];
            $result = DataMapper::source(['name' => 'John', 'age' => 30])
                ->target([])
                ->template($template)
                ->map();

            expect($result->getTemplate())->toBe($template);
        });

        it('returns empty array for empty template', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTemplate())->toBe([]);
        });
    });

    describe('toJson()', function(): void {
        it('converts array result to JSON', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $json = $result->toJson();

            expect($json)->toBeString();
            expect(json_decode($json, true))->toBe(['name' => 'John']);
        });

        it('converts nested array to JSON', function(): void {
            $result = DataMapper::source(['user' => ['name' => 'John']])
                ->target([])
                ->template(['user.name' => '{{ user.name }}'])
                ->map();

            $json = $result->toJson();

            expect($json)->toBeString();
            $decoded = json_decode($json, true);
            expect($decoded)->toHaveKey('user');
            expect($decoded['user'])->toHaveKey('name');
        });

        it('converts empty array to JSON', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            $json = $result->toJson();

            expect($json)->toBe('[]');
        });

        it('handles JSON encoding options', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $json = $result->toJson(JSON_PRETTY_PRINT);

            expect($json)->toBeString();
            expect($json)->toContain("\n");
        });

        it('throws exception for invalid JSON', function(): void {
            // Create a result with non-UTF8 data that can't be JSON encoded
            $result = new DataMapperResult(
                ['invalid' => "\xB1\x31"],
                [],
                []
            );

            expect(fn(): string => $result->toJson())->toThrow(ConversionException::class);
        });
    });

    describe('toArray()', function(): void {
        it('returns array for array result', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->toArray())->toBeArray();
            expect($result->toArray())->toBe(['name' => 'John']);
        });

        it('converts object to array', function(): void {
            $target = new stdClass();
            $result = DataMapper::source(['name' => 'John'])
                ->target($target)
                ->template(['name' => '{{ name }}'])
                ->map();

            $array = $result->toArray();

            expect($array)->toBeArray();
            expect($array)->toHaveKey('name');
        });

        it('returns empty array for empty result', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->toArray())->toBe([]);
        });

        it('converts nested objects to arrays', function(): void {
            $target = new stdClass();
            $target->user = new stdClass();
            $target->user->name = 'John';

            $result = new DataMapperResult($target, [], []);
            $array = $result->toArray();

            expect($array)->toBeArray();
            expect($array['user'])->toBeArray();
            expect($array['user']['name'])->toBe('John');
        });
    });

    describe('query()', function(): void {
        it('returns DataFilter instance', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $query = $result->query();

            expect($query)->toBeInstanceOf(DataFilter::class);
        });

        it('can query the result', function(): void {
            $result = DataMapper::source([
                'users' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25],
                ],
            ])
                ->target([])
                ->template(['users' => '{{ users }}'])
                ->map();

            $query = $result->query();

            expect($query)->toBeInstanceOf(DataFilter::class);
        });
    });

    describe('Edge cases', function(): void {
        it('handles null result', function(): void {
            $result = new DataMapperResult(null, [], []);

            expect($result->getTarget())->toBeNull();
        });

        it('handles string result', function(): void {
            $result = new DataMapperResult('test string', [], []);

            expect($result->getTarget())->toBe('test string');
        });

        it('handles numeric result', function(): void {
            $result = new DataMapperResult(42, [], []);

            expect($result->getTarget())->toBe(42);
        });

        it('handles boolean result', function(): void {
            $result = new DataMapperResult(true, [], []);

            expect($result->getTarget())->toBeTrue();
        });

        it('toArray throws exception for non-convertible types', function(): void {
            $result = new DataMapperResult(42, [], []);

            expect(fn(): array => $result->toArray())->toThrow(ConversionException::class);
        });
    });
});
