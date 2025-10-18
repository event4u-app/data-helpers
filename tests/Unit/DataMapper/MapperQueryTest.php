<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\MapperQuery;

describe('MapperQuery', function(): void {
    describe('Construction', function(): void {
        it('creates query from mapper', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3]]);
            $query = $mapper->query('items.*');

            expect($query)->toBeInstanceOf(MapperQuery::class);
        });

        it('stores wildcard path', function(): void {
            $mapper = DataMapper::source(['items' => [1, 2, 3]]);
            $query = $mapper->query('items.*');

            expect($query->getWildcardPath())->toBe('items.*');
        });
    });

    describe('where()', function(): void {
        it('adds where condition with 3 arguments', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('status', '=', 'active');

            $conditions = $query->getWhereConditions();
            expect($conditions)->toHaveCount(1);
            expect($conditions[0]['field'])->toBe('status');
            expect($conditions[0]['operator'])->toBe('=');
            expect($conditions[0]['value'])->toBe('active');
        });

        it('adds where condition with 2 arguments (defaults to =)', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('status', 'active');

            $conditions = $query->getWhereConditions();
            expect($conditions)->toHaveCount(1);
            expect($conditions[0]['field'])->toBe('status');
            expect($conditions[0]['operator'])->toBe('=');
            expect($conditions[0]['value'])->toBe('active');
        });

        it('adds multiple where conditions', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('status', 'active')
                ->where('age', '>', 18);

            $conditions = $query->getWhereConditions();
            expect($conditions)->toHaveCount(2);
        });

        it('returns self for chaining', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*');
            $returned = $query->where('status', 'active');

            expect($returned)->toBe($query);
        });

        it('handles different operators', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('age', '>', 18)
                ->where('age', '<', 65)
                ->where('status', '!=', 'inactive')
                ->where('name', 'LIKE', '%John%');

            $conditions = $query->getWhereConditions();
            expect($conditions)->toHaveCount(4);
            expect($conditions[0]['operator'])->toBe('>');
            expect($conditions[1]['operator'])->toBe('<');
            expect($conditions[2]['operator'])->toBe('!=');
            expect($conditions[3]['operator'])->toBe('LIKE');
        });

        it('handles null values', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('deleted_at', null);

            $conditions = $query->getWhereConditions();
            expect($conditions[0]['value'])->toBeNull();
        });

        it('handles array values', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('status', 'IN', ['active', 'pending']);

            $conditions = $query->getWhereConditions();
            expect($conditions[0]['value'])->toBe(['active', 'pending']);
        });
    });

    describe('orderBy()', function(): void {
        it('adds order by condition with ASC', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->orderBy('name', 'ASC');

            $conditions = $query->getOrderByConditions();
            expect($conditions)->toHaveCount(1);
            expect($conditions[0]['field'])->toBe('name');
            expect($conditions[0]['direction'])->toBe('ASC');
        });

        it('adds order by condition with DESC', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->orderBy('name', 'DESC');

            $conditions = $query->getOrderByConditions();
            expect($conditions[0]['direction'])->toBe('DESC');
        });

        it('defaults to ASC when no direction specified', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->orderBy('name');

            $conditions = $query->getOrderByConditions();
            expect($conditions[0]['direction'])->toBe('ASC');
        });

        it('adds multiple order by conditions', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->orderBy('status', 'ASC')
                ->orderBy('name', 'DESC');

            $conditions = $query->getOrderByConditions();
            expect($conditions)->toHaveCount(2);
        });

        it('normalizes direction to uppercase', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->orderBy('name', 'asc')
                ->orderBy('age', 'desc');

            $conditions = $query->getOrderByConditions();
            expect($conditions[0]['direction'])->toBe('ASC');
            expect($conditions[1]['direction'])->toBe('DESC');
        });

        it('returns self for chaining', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*');
            $returned = $query->orderBy('name');

            expect($returned)->toBe($query);
        });
    });

    describe('limit()', function(): void {
        it('sets limit', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->limit(10);

            expect($query->getLimit())->toBe(10);
        });

        it('sets limit to 0', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->limit(0);

            expect($query->getLimit())->toBe(0);
        });

        it('overwrites previous limit', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->limit(10)
                ->limit(5);

            expect($query->getLimit())->toBe(5);
        });

        it('returns self for chaining', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*');
            $returned = $query->limit(10);

            expect($returned)->toBe($query);
        });
    });

    describe('offset()', function(): void {
        it('sets offset', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->offset(5);

            expect($query->getOffset())->toBe(5);
        });

        it('sets offset to 0', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->offset(0);

            expect($query->getOffset())->toBe(0);
        });

        it('overwrites previous offset', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->offset(10)
                ->offset(5);

            expect($query->getOffset())->toBe(5);
        });

        it('returns self for chaining', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*');
            $returned = $query->offset(5);

            expect($returned)->toBe($query);
        });
    });

    describe('groupBy()', function(): void {
        it('adds group by field', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->groupBy('category');

            $fields = $query->getGroupByFields();
            expect($fields)->toHaveCount(1);
            expect($fields[0])->toBe('category');
        });

        it('adds multiple group by fields', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->groupBy('category')
                ->groupBy('status');

            $fields = $query->getGroupByFields();
            expect($fields)->toHaveCount(2);
            expect($fields)->toBe(['category', 'status']);
        });

        it('returns self for chaining', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*');
            $returned = $query->groupBy('category');

            expect($returned)->toBe($query);
        });
    });

    describe('end()', function(): void {
        it('returns parent mapper', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*');
            $returned = $query->end();

            expect($returned)->toBe($mapper);
            expect($returned)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('allows continuing mapper chain', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $returned = $mapper->query('items.*')
                ->where('status', 'active')
                ->end()
                ->target([]);

            expect($returned)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('Method chaining', function(): void {
        it('can chain all methods', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->where('status', 'active')
                ->where('age', '>', 18)
                ->orderBy('name', 'ASC')
                ->orderBy('age', 'DESC')
                ->limit(10)
                ->offset(5)
                ->groupBy('category');

            expect($query)->toBeInstanceOf(MapperQuery::class);
            expect($query->getWhereConditions())->toHaveCount(2);
            expect($query->getOrderByConditions())->toHaveCount(2);
            expect($query->getLimit())->toBe(10);
            expect($query->getOffset())->toBe(5);
            expect($query->getGroupByFields())->toHaveCount(1);
        });

        it('can chain in any order', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $query = $mapper->query('items.*')
                ->limit(10)
                ->where('status', 'active')
                ->groupBy('category')
                ->orderBy('name')
                ->offset(5);

            expect($query)->toBeInstanceOf(MapperQuery::class);
        });

        it('can chain and return to mapper', function(): void {
            $mapper = DataMapper::source(['items' => []]);
            $returned = $mapper->query('items.*')
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(10)
                ->end()
                ->target([])
                ->template(['items' => '{{ items }}']);

            expect($returned)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('Multiple queries on same mapper', function(): void {
        it('can create multiple independent queries', function(): void {
            $mapper = DataMapper::source(['items' => [], 'users' => []]);
            
            $query1 = $mapper->query('items.*')
                ->where('status', 'active');
            
            $query2 = $mapper->query('users.*')
                ->where('role', 'admin');

            expect($query1)->not->toBe($query2);
            expect($query1->getWildcardPath())->toBe('items.*');
            expect($query2->getWildcardPath())->toBe('users.*');
        });

        it('queries are stored in mapper', function(): void {
            $mapper = DataMapper::source(['items' => [], 'users' => []]);
            
            $mapper->query('items.*')->where('status', 'active')->end();
            $mapper->query('users.*')->where('role', 'admin')->end();

            $queries = $mapper->getQueries();
            expect($queries)->toHaveCount(2);
            expect($queries)->toHaveKey('items.*');
            expect($queries)->toHaveKey('users.*');
        });
    });
});
