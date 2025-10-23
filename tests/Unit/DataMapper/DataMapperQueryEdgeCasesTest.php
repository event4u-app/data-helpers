<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapperQuery - Edge Cases', function(): void {
    it('handles empty source array', function(): void {
        $result = DataMapper::query()
            ->source('products', [])
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles empty result after filtering', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 50],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->where('price', '>', 1000)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles null values in wildcard data with where conditions', function(): void {
        $data = [
            ['id' => 1, 'name' => 'Alice', 'status' => 'active'],
            ['id' => 2, 'name' => 'Bob', 'status' => 'inactive'],
            ['id' => 3, 'name' => 'Charlie', 'status' => 'active'],
        ];

        $result = DataMapper::query()
            ->source('users', $data)
            ->where('status', '=', 'active')
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles deeply nested wildcard paths', function(): void {
        $data = [
            ['id' => 1, 'user' => ['profile' => ['address' => ['city' => 'Berlin']]]],
            ['id' => 2, 'user' => ['profile' => ['address' => ['city' => 'Munich']]]],
            ['id' => 3, 'user' => ['profile' => ['address' => ['city' => 'Berlin']]]],
        ];

        $result = DataMapper::query()
            ->source('data', $data)
            ->where('user.profile.address.city', 'Berlin')
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles limit of 0', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->limit(0)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles offset larger than array size', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->offset(10)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles multiple ORDER BY clauses', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A', 'price' => 100],
            ['id' => 2, 'category' => 'B', 'price' => 50],
            ['id' => 3, 'category' => 'A', 'price' => 75],
            ['id' => 4, 'category' => 'B', 'price' => 200],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->orderBy('category', 'ASC')
            ->orderBy('price', 'DESC')
            ->reindex()
            ->get();

        expect($result)->toBeArray();
        expect($result[0]['id'])->toBe(1); // A, 100
        expect($result[1]['id'])->toBe(3); // A, 75
        expect($result[2]['id'])->toBe(4); // B, 200
        expect($result[3]['id'])->toBe(2); // B, 50
    });

    it('handles BETWEEN with equal min and max', function(): void {
        $products = [
            ['id' => 1, 'price' => 50],
            ['id' => 2, 'price' => 100],
            ['id' => 3, 'price' => 150],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->between('price', 100, 100)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
    });

    it('handles NOT BETWEEN', function(): void {
        $products = [
            ['id' => 1, 'price' => 50],
            ['id' => 2, 'price' => 100],
            ['id' => 3, 'price' => 150],
            ['id' => 4, 'price' => 200],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->notBetween('price', 100, 150)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles WHERE NOT IN', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A'],
            ['id' => 2, 'category' => 'B'],
            ['id' => 3, 'category' => 'C'],
            ['id' => 4, 'category' => 'D'],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->whereNotIn('category', ['B', 'D'])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles empty WHERE IN array', function(): void {
        $products = [
            ['id' => 1, 'category' => 'A'],
            ['id' => 2, 'category' => 'B'],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->whereIn('category', [])
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles LIKE with no wildcards', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->like('name', 'Laptop')
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
    });

    it('handles LIKE with middle wildcard', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop Pro'],
            ['id' => 2, 'name' => 'Laptop Air'],
            ['id' => 3, 'name' => 'Desktop PC'],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->like('name', '%top%')
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(3); // Laptop Pro, Laptop Air, Desktop
    });

    it('handles boolean values in wildcards', function(): void {
        $products = [
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
            ['id' => 3, 'active' => true],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->where('active', '=', true)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles multiple where conditions', function(): void {
        $data = [
            ['id' => 1, 'category' => 'A', 'price' => 100],
            ['id' => 2, 'category' => 'B', 'price' => 50],
            ['id' => 3, 'category' => 'A', 'price' => 150],
        ];

        $result = DataMapper::query()
            ->source('products', $data)
            ->where('category', '=', 'A')
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
    });

    it('handles distinct with multiple duplicates', function(): void {
        $data = [
            ['id' => 1, 'category' => 'A'],
            ['id' => 2, 'category' => 'A'],
            ['id' => 3, 'category' => 'A'],
            ['id' => 4, 'category' => 'B'],
            ['id' => 5, 'category' => 'B'],
        ];

        $result = DataMapper::query()
            ->source('data', $data)
            ->distinct('category')
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('handles chaining all operators', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'Electronics', 'price' => 1200, 'stock' => 5],
            ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25, 'stock' => 50],
            ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 300, 'stock' => 10],
            ['id' => 4, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 150, 'stock' => 20],
            ['id' => 5, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400, 'stock' => 15],
            ['id' => 6, 'name' => 'Keyboard', 'category' => 'Electronics', 'price' => 80, 'stock' => 30],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->where('category', '=', 'Electronics')
            ->where('price', '>', 50)
            ->orderBy('price', 'DESC')
            ->offset(1)
            ->limit(2)
            ->reindex()
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Monitor'); // 400
        expect($result[1]['name'])->toBe('Keyboard'); // 80
    });
});
