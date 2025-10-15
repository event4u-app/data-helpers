<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperResult;
use event4u\DataHelpers\Exceptions\ConversionException;

describe('DataMapperResult', function () {
    describe('getTarget()', function () {
        it('returns the target', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeArray();
            expect($result->getTarget())->toHaveKey('name');
        });

        it('returns array target', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeArray();
        });

        it('returns object target', function () {
            $target = new stdClass();
            $result = DataMapper::source(['name' => 'John'])
                ->target($target)
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeObject();
        });

        it('returns empty array for empty mapping', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTarget())->toBe([]);
        });
    });

    describe('getSource()', function () {
        it('returns the original source', function () {
            $source = ['name' => 'John', 'age' => 30];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getSource())->toBe($source);
        });

        it('returns array source', function () {
            $source = ['name' => 'John'];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getSource())->toBeArray();
        });

        it('returns object source', function () {
            $source = (object) ['name' => 'John'];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getSource())->toBeObject();
        });
    });

    describe('getTemplate()', function () {
        it('returns the template', function () {
            $template = ['name' => '{{ name }}', 'age' => '{{ age }}'];
            $result = DataMapper::source(['name' => 'John', 'age' => 30])
                ->target([])
                ->template($template)
                ->map();

            expect($result->getTemplate())->toBe($template);
        });

        it('returns empty array for empty template', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTemplate())->toBe([]);
        });
    });

    describe('toJson()', function () {
        it('converts array result to JSON', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $json = $result->toJson();

            expect($json)->toBeString();
            expect(json_decode($json, true))->toBe(['name' => 'John']);
        });

        it('converts nested array to JSON', function () {
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

        it('converts empty array to JSON', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            $json = $result->toJson();

            expect($json)->toBe('[]');
        });

        it('handles JSON encoding options', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $json = $result->toJson(JSON_PRETTY_PRINT);

            expect($json)->toBeString();
            expect($json)->toContain("\n");
        });

        it('throws exception for invalid JSON', function () {
            // Create a result with non-UTF8 data that can't be JSON encoded
            $result = new DataMapperResult(
                ['invalid' => "\xB1\x31"],
                [],
                []
            );

            expect(fn () => $result->toJson())->toThrow(ConversionException::class);
        });
    });

    describe('toArray()', function () {
        it('returns array for array result', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->toArray())->toBeArray();
            expect($result->toArray())->toBe(['name' => 'John']);
        });

        it('converts object to array', function () {
            $target = new stdClass();
            $result = DataMapper::source(['name' => 'John'])
                ->target($target)
                ->template(['name' => '{{ name }}'])
                ->map();

            $array = $result->toArray();

            expect($array)->toBeArray();
            expect($array)->toHaveKey('name');
        });

        it('returns empty array for empty result', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->toArray())->toBe([]);
        });

        it('converts nested objects to arrays', function () {
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

    describe('query()', function () {
        it('returns DataFilter instance', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $query = $result->query();

            expect($query)->toBeInstanceOf(DataFilter::class);
        });

        it('can query the result', function () {
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

    describe('Edge cases', function () {
        it('handles null result', function () {
            $result = new DataMapperResult(null, [], []);

            expect($result->getTarget())->toBeNull();
        });

        it('handles string result', function () {
            $result = new DataMapperResult('test string', [], []);

            expect($result->getTarget())->toBe('test string');
        });

        it('handles numeric result', function () {
            $result = new DataMapperResult(42, [], []);

            expect($result->getTarget())->toBe(42);
        });

        it('handles boolean result', function () {
            $result = new DataMapperResult(true, [], []);

            expect($result->getTarget())->toBeTrue();
        });

        it('toArray throws exception for non-convertible types', function () {
            $result = new DataMapperResult(42, [], []);

            expect(fn () => $result->toArray())->toThrow(ConversionException::class);
        });
    });
});
