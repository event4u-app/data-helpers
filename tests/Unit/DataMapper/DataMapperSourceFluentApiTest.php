<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\MapperQuery;
use event4u\DataHelpers\DataMapper\MappingOptions;

describe('FluentDataMapper Fluent API', function () {
    describe('target()', function () {
        it('sets array target', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets object target', function () {
            $target = new stdClass();
            $mapper = DataMapper::source(['name' => 'John'])
                ->target($target);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets null target', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target(null);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can be called multiple times (last wins)', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->target(['existing' => 'data'])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($mapper->getTarget())->toHaveKey('name');
        });
    });

    describe('template()', function () {
        it('sets simple template', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template(['name' => '{{ name }}']);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets nested template', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template([
                    'user' => [
                        'name' => '{{ name }}',
                    ],
                ]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets empty template', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can be called multiple times (last wins)', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template(['old' => '{{ name }}'])
                ->template(['new' => '{{ name }}'])
                ->target([])
                ->map();

            expect($mapper->getTarget())->toHaveKey('new');
            expect($mapper->getTarget())->not->toHaveKey('old');
        });
    });

    describe('skipNull()', function () {
        it('sets skipNull to true', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->skipNull(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets skipNull to false', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->skipNull(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('affects mapping result when true', function () {
            $result = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->skipNull(true)
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->not->toHaveKey('age');
        });

        it('affects mapping result when false', function () {
            $result = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->skipNull(false)
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->toHaveKey('age');
            expect($result->getTarget()['age'])->toBeNull();
        });
    });

    describe('reindexWildcard()', function () {
        it('sets reindexWildcard to true', function () {
            $mapper = DataMapper::source(['items' => [1, 2, 3]])
                ->reindexWildcard(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets reindexWildcard to false', function () {
            $mapper = DataMapper::source(['items' => [1, 2, 3]])
                ->reindexWildcard(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('trimValues()', function () {
        it('sets trimValues to true', function () {
            $mapper = DataMapper::source(['name' => '  John  '])
                ->trimValues(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets trimValues to false', function () {
            $mapper = DataMapper::source(['name' => '  John  '])
                ->trimValues(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('affects mapping result when true', function () {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)
                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });

        it('affects mapping result when false', function () {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(false)
                ->map();

            expect($result->getTarget()['name'])->toBe('  John  ');
        });
    });

    describe('caseInsensitiveReplace()', function () {
        it('sets caseInsensitiveReplace to true', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->caseInsensitiveReplace(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets caseInsensitiveReplace to false', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->caseInsensitiveReplace(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('hooks()', function () {
        it('sets hooks', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->hooks(['beforeMap' => fn () => null]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets empty hooks', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->hooks([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('options()', function () {
        it('sets MappingOptions', function () {
            $options = new MappingOptions();
            $mapper = DataMapper::source(['name' => 'John'])
                ->options($options);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('query()', function () {
        it('creates MapperQuery', function () {
            $mapper = DataMapper::source(['items' => [1, 2, 3]]);
            $query = $mapper->query('items.*');

            expect($query)->toBeInstanceOf(MapperQuery::class);
        });

        it('returns to mapper with end()', function () {
            $mapper = DataMapper::source(['items' => [1, 2, 3]]);
            $returned = $mapper->query('items.*')->end();

            expect($returned)->toBe($mapper);
        });

        it('can create multiple queries', function () {
            $mapper = DataMapper::source(['items' => [1, 2, 3], 'users' => []]);
            $query1 = $mapper->query('items.*');
            $query2 = $mapper->query('users.*');

            expect($query1)->toBeInstanceOf(MapperQuery::class);
            expect($query2)->toBeInstanceOf(MapperQuery::class);
            expect($query1)->not->toBe($query2);
        });
    });

    describe('copy()', function () {
        it('creates a copy of the mapper', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}']);

            $copy = $mapper->copy();

            expect($copy)->toBeInstanceOf(FluentDataMapper::class);
            expect($copy)->not->toBe($mapper);
        });

        it('copy has same configuration', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->skipNull(false);

            $copy = $mapper->copy();

            $result1 = $mapper->map();
            $result2 = $copy->map();

            expect($result1->getTarget())->toEqual($result2->getTarget());
        });

        it('copy is independent', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}']);

            $copy = $mapper->copy()
                ->template(['different' => '{{ name }}']);

            $result1 = $mapper->map();
            $result2 = $copy->map();

            expect($result1->getTarget())->toHaveKey('name');
            expect($result2->getTarget())->toHaveKey('different');
        });
    });

    describe('Method chaining', function () {
        it('can chain all methods', function () {
            $mapper = DataMapper::source(['name' => 'John', 'age' => 30])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->skipNull(true)
                ->reindexWildcard(false)
                ->trimValues(true)
                ->caseInsensitiveReplace(false)
                ->hooks([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can chain in any order', function () {
            $mapper = DataMapper::source(['name' => 'John'])
                ->skipNull(true)
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)
                ->target([])
                ->reindexWildcard(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can chain with query', function () {
            $mapper = DataMapper::source(['items' => [1, 2, 3]])
                ->target([])
                ->template(['items' => '{{ items.* }}'])
                ->query('items.*')
                    ->where('value', '>', 1)
                    ->limit(2)
                    ->end()
                ->skipNull(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });
});
