<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperResult;

describe('FluentDataMapper Mapping', function () {
    describe('map()', function () {
        it('returns DataMapperResult', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });

        it('maps simple fields', function () {
            $result = DataMapper::source(['firstName' => 'John', 'lastName' => 'Doe'])
                ->target([])
                ->template([
                    'first' => '{{ firstName }}',
                    'last' => '{{ lastName }}',
                ])
                ->map();

            expect($result->getTarget())->toBe([
                'first' => 'John',
                'last' => 'Doe',
            ]);
        });

        it('maps nested fields', function () {
            $result = DataMapper::source([
                'user' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
            ])
                ->target([])
                ->template([
                    'profile.name' => '{{ user.name }}',
                    'profile.email' => '{{ user.email }}',
                ])
                ->map();

            expect($result->getTarget())->toBe([
                'profile' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
            ]);
        });

        it('maps with wildcards', function () {
            $result = DataMapper::source([
                'users' => [
                    ['name' => 'John'],
                    ['name' => 'Jane'],
                ],
            ])
                ->target([])
                ->template([
                    'names' => '{{ users.*.name }}',
                ])
                ->map();

            expect($result->getTarget()['names'])->toBe(['John', 'Jane']);
        });

        it('maps empty source', function () {
            $result = DataMapper::source([])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBe([]);
        });

        it('maps with empty template', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTarget())->toBe([]);
        });

        it('maps to existing target', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target(['existing' => 'value'])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->toHaveKey('existing');
        });

        it('maps with null values and skipNull true', function () {
            $result = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->skipNull(true)
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->not->toHaveKey('age');
        });

        it('maps with null values and skipNull false', function () {
            $result = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->skipNull(false)
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->toHaveKey('age');
            expect($result->getTarget()['age'])->toBeNull();
        });

        it('maps with trimValues true', function () {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)
                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });

        it('maps with trimValues false', function () {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(false)
                ->map();

            expect($result->getTarget()['name'])->toBe('  John  ');
        });

        it('maps deeply nested structures', function () {
            $result = DataMapper::source([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
            ])
                ->target([])
                ->template([
                    'result.deep.value' => '{{ level1.level2.level3.value }}',
                ])
                ->map();

            expect($result->getTarget())->toBe([
                'result' => [
                    'deep' => [
                        'value' => 'deep',
                    ],
                ],
            ]);
        });

        it('maps multiple times with same mapper', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}']);

            $result1 = $mapper->map();
            $result2 = $mapper->map();

            expect($result1->getTarget())->toEqual($result2->getTarget());
        });

        it('maps with withQuery parameter true', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map(true);

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });

        it('maps with withQuery parameter false', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map(false);

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });
    });

    describe('reverseMap()', function () {
        it('returns DataMapperResult', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->reverseMap();

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });

        it('performs reverse mapping', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['userName' => '{{ name }}'])
                ->reverseMap();

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });

        it('reverseMap with withQuery parameter true', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->reverseMap(true);

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });

        it('reverseMap with withQuery parameter false', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->reverseMap(false);

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });
    });

    describe('Mapping with different source types', function () {
        it('maps from array source', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });

        it('maps from object source', function () {
            $source = (object) ['name' => 'John'];
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });

        it('maps from stdClass source', function () {
            $source = new stdClass();
            $source->name = 'John';
            $result = DataMapper::source($source)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });
    });

    describe('Mapping with different target types', function () {
        it('maps to array target', function () {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeArray();
        });

        it('maps to object target', function () {
            $target = new stdClass();
            $result = DataMapper::source(['name' => 'John'])
                ->target($target)
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBeObject();
        });
    });

    describe('Complex mapping scenarios', function () {
        it('maps with multiple nested levels', function () {
            $source = [
                'company' => [
                    'departments' => [
                        ['name' => 'IT', 'employees' => [['name' => 'John'], ['name' => 'Jane']]],
                        ['name' => 'HR', 'employees' => [['name' => 'Bob']]],
                    ],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template([
                    'deptNames' => '{{ company.departments.*.name }}',
                ])
                ->map();

            expect($result->getTarget()['deptNames'])->toBe(['IT', 'HR']);
        });

        it('maps with mixed data types', function () {
            $source = [
                'string' => 'text',
                'number' => 42,
                'float' => 3.14,
                'bool' => true,
                'null' => null,
                'array' => [1, 2, 3],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template([
                    'string' => '{{ string }}',
                    'number' => '{{ number }}',
                    'float' => '{{ float }}',
                    'bool' => '{{ bool }}',
                    'null' => '{{ null }}',
                    'array' => '{{ array }}',
                ])
                ->skipNull(false)
                ->map();

            expect($result->getTarget())->toHaveKey('string');
            expect($result->getTarget())->toHaveKey('number');
            expect($result->getTarget())->toHaveKey('float');
            expect($result->getTarget())->toHaveKey('bool');
            expect($result->getTarget())->toHaveKey('null');
            expect($result->getTarget())->toHaveKey('array');
        });
    });
});
