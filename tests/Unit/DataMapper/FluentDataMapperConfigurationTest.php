<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\MapperQuery;
use event4u\DataHelpers\DataMapper\MappingOptions;

describe('FluentDataMapper Fluent API', function(): void {
    describe('target()', function(): void {
        it('sets array target', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets object target', function(): void {
            $target = new stdClass();
            $mapper = DataMapper::source(['name' => 'John'])
                ->target($target);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets null target', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target(null);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can be called multiple times (last wins)', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->target(['existing' => 'data'])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)

                ->map();

            expect($mapper->getTarget())->toHaveKey('name');
        });
    });

    describe('template()', function(): void {
        it('sets simple template', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template(['name' => '{{ name }}']);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets nested template', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template([
                    'user' => [
                        'name' => '{{ name }}',
                    ],
                ]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets empty template', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can be called multiple times (last wins)', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template(['old' => '{{ name }}'])
                ->template(['new' => '{{ name }}'])
                ->target([])
                ->trimValues(true)

                ->map();

            expect($mapper->getTarget())->toHaveKey('new');
            expect($mapper->getTarget())->not->toHaveKey('old');
        });
    });

    describe('skipNull()', function(): void {
        it('sets skipNull to true', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->skipNull(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets skipNull to false', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->skipNull(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('affects mapping result when true', function(): void {
            $result = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->not->toHaveKey('age');
        });

        it('affects mapping result when false', function(): void {
            $result = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->skipNull(false)
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget())->toHaveKey('age');
            expect($result->getTarget()['age'])->toBeNull();
        });
    });

    describe('reindexWildcard()', function(): void {
        it('sets reindexWildcard to true', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3]])
                ->reindexWildcard(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets reindexWildcard to false', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3]])
                ->reindexWildcard(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('trimValues()', function(): void {
        it('sets trimValues to true', function(): void {
            $mapper = DataMapper::source(['name' => '  John  '])
                ->trimValues(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets trimValues to false', function(): void {
            $mapper = DataMapper::source(['name' => '  John  '])
                ->trimValues(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('affects mapping result when true', function(): void {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)

                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });

        it('affects mapping result when false', function(): void {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(false)
                ->trimValues(true)

                ->map();

            expect($result->getTarget()['name'])->toBe('John');
        });
    });

    describe('caseInsensitiveReplace()', function(): void {
        it('sets caseInsensitiveReplace to true', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->caseInsensitiveReplace(true);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets caseInsensitiveReplace to false', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->caseInsensitiveReplace(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('hooks()', function(): void {
        it('sets hooks', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->hooks(['beforeMap' => fn(): null => null]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('sets empty hooks', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->hooks([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('options()', function(): void {
        it('sets MappingOptions', function(): void {
            $options = new MappingOptions();
            $mapper = DataMapper::source(['name' => 'John'])
                ->options($options);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('query()', function(): void {
        it('creates MapperQuery', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3]]);
            $query = $mapper->query('items.*');

            expect($query)->toBeInstanceOf(MapperQuery::class);
        });

        it('returns to mapper with end()', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3]]);
            $returned = $mapper->query('items.*')->end();

            expect($returned)->toBe($mapper);
        });

        it('can create multiple queries', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3], 'users' => []]);
            $query1 = $mapper->query('items.*');
            $query2 = $mapper->query('users.*');

            expect($query1)->toBeInstanceOf(MapperQuery::class);
            expect($query2)->toBeInstanceOf(MapperQuery::class);
            expect($query1)->not->toBe($query2);
        });
    });

    describe('copy()', function(): void {
        it('creates a copy of the mapper', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}']);

            $copy = $mapper->copy();

            expect($copy)->toBeInstanceOf(FluentDataMapper::class);
            expect($copy)->not->toBe($mapper);
        });

        it('copy has same configuration', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->skipNull(false);

            $copy = $mapper->copy();

            $result1 = $mapper->map();
            $result2 = $copy->map();

            expect($result1->getTarget())->toEqual($result2->getTarget());
        });

        it('copy is independent', function(): void {
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

    describe('Method chaining', function(): void {
        it('can chain all methods', function(): void {
            $mapper = DataMapper::source(['name' => 'John', 'age' => 30])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->hooks([]);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can chain in any order', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->template(['name' => '{{ name }}'])
                ->target([])
                ->reindexWildcard(false);

            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('can chain with query', function(): void {
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
