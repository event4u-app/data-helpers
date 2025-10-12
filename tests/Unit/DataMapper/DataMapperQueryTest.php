<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\DataMapperQuery;

describe('DataMapperQuery - Basic Usage', function(): void {
    it('creates a query with source', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
            ['id' => 2, 'name' => 'Mouse', 'price' => 50],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->get();

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Laptop');
    });

    it('filters with WHERE clause', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1000],
            ['id' => 2, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 500],
            ['id' => 3, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 50],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->where('category', 'Electronics')
            ->get();

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Laptop');
        expect($result[2]['name'])->toBe('Mouse');
    });

    it('filters with comparison operators', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
            ['id' => 2, 'name' => 'Mouse', 'price' => 50],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 150],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->where('price', '>', 100)
            ->get();

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Laptop');
        expect($result[2]['name'])->toBe('Keyboard');
    });

    it('sorts with ORDER BY', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
            ['id' => 2, 'name' => 'Mouse', 'price' => 50],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 150],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->orderBy('price', 'DESC')
            ->reindex()
            ->get();

        expect($result)->toHaveCount(3);
        expect($result[0]['name'])->toBe('Laptop');
        expect($result[1]['name'])->toBe('Keyboard');
        expect($result[2]['name'])->toBe('Mouse');
    });

    it('limits results with LIMIT', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
            ['id' => 2, 'name' => 'Mouse', 'price' => 50],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 150],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->limit(2)
            ->get();

        expect($result)->toHaveCount(2);
    });

    it('skips items with OFFSET', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
            ['id' => 2, 'name' => 'Mouse', 'price' => 50],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 150],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->offset(1)
            ->reindex()
            ->get();

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Mouse');
    });
});

describe('DataMapperQuery - WHERE with Closures', function(): void {
    it('supports nested WHERE with closure', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1000],
            ['id' => 2, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 500],
            ['id' => 3, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 50],
            ['id' => 4, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 200],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->where(function($query) {
                $query->where('category', 'Electronics')
                      ->where('price', '>', 100);
            })
            ->get();

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('Laptop');
    });

    it('supports OR WHERE with closure', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1000],
            ['id' => 2, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 500],
            ['id' => 3, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 50],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->where('category', 'Electronics')
            ->orWhere(function($query) {
                $query->where('price', '>', 400);
            })
            ->get();

        expect($result)->toHaveCount(3);
    });
});

describe('DataMapperQuery - GROUP BY', function(): void {
    it('groups by field with aggregations', function(): void {
        $sales = [
            ['id' => 1, 'category' => 'Electronics', 'amount' => 1000],
            ['id' => 2, 'category' => 'Furniture', 'amount' => 500],
            ['id' => 3, 'category' => 'Electronics', 'amount' => 150],
            ['id' => 4, 'category' => 'Furniture', 'amount' => 300],
        ];

        $result = DataMapperQuery::query()
            ->source('sales', $sales)
            ->groupBy('category')
            ->aggregate('total', 'SUM', 'amount')
            ->aggregate('count', 'COUNT')
            ->reindex()
            ->get();

        expect($result)->toHaveCount(2);

        // Find Electronics group
        $electronics = null;
        foreach ($result as $item) {
            if ('Electronics' === $item['category']) {
                $electronics = $item;
                break;
            }
        }

        expect($electronics)->not->toBeNull();
        expect($electronics['total'])->toBe(1150);
        expect($electronics['count'])->toBe(2);
    });

    it('supports HAVING clause', function(): void {
        $sales = [
            ['id' => 1, 'category' => 'Electronics', 'amount' => 1000],
            ['id' => 2, 'category' => 'Furniture', 'amount' => 500],
            ['id' => 3, 'category' => 'Electronics', 'amount' => 150],
            ['id' => 4, 'category' => 'Books', 'amount' => 50],
        ];

        $result = DataMapperQuery::query()
            ->source('sales', $sales)
            ->groupBy('category')
            ->aggregate('total', 'SUM', 'amount')
            ->having('total', '>=', 500)
            ->reindex()
            ->get();

        expect($result)->toHaveCount(2); // Electronics (1150) and Furniture (500)
    });
});

describe('DataMapperQuery - Chaining', function(): void {
    it('chains multiple operators', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1000, 'stock' => 5],
            ['id' => 2, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 500, 'stock' => 0],
            ['id' => 3, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 50, 'stock' => 20],
            ['id' => 4, 'name' => 'Keyboard', 'category' => 'Electronics', 'price' => 150, 'stock' => 10],
            ['id' => 5, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 200, 'stock' => 3],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->where('category', 'Electronics')
            ->where('stock', '>', 0)
            ->orderBy('price', 'DESC')
            ->limit(2)
            ->reindex()
            ->get();

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Laptop');
        expect($result[1]['name'])->toBe('Keyboard');
    });

    it('can be built in any order', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1000],
            ['id' => 2, 'name' => 'Mouse', 'price' => 50],
        ];

        $result = DataMapperQuery::query()
            ->limit(1)
            ->source('products', $products)
            ->orderBy('price', 'DESC')
            ->get();

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('Laptop');
    });
});

describe('DataMapperQuery - DISTINCT and LIKE', function(): void {
    it('removes duplicates with DISTINCT', function(): void {
        $products = [
            ['id' => 1, 'category' => 'Electronics'],
            ['id' => 2, 'category' => 'Furniture'],
            ['id' => 3, 'category' => 'Electronics'],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->distinct('category')
            ->reindex()
            ->get();

        expect($result)->toHaveCount(2);
    });

    it('filters with LIKE pattern', function(): void {
        $products = [
            ['id' => 1, 'name' => 'iPhone 13'],
            ['id' => 2, 'name' => 'Samsung Galaxy'],
            ['id' => 3, 'name' => 'iPhone 14'],
        ];

        $result = DataMapperQuery::query()
            ->source('products', $products)
            ->like('name', 'iPhone%')
            ->reindex()
            ->get();

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('iPhone 13');
    });
});

