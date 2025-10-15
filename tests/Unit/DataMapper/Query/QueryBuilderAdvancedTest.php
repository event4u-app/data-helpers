<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('QueryBuilder Advanced Features', function(): void {
    it('handles between operator', function(): void {
        $products = [
            ['name' => 'Item A', 'price' => 10],
            ['name' => 'Item B', 'price' => 50],
            ['name' => 'Item C', 'price' => 100],
            ['name' => 'Item D', 'price' => 150],
            ['name' => 'Item E', 'price' => 200],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->between('price', 50, 150)
            ->get();

        $values = array_values($result);
        expect($values)->toHaveCount(3);
        expect($values[0]['price'])->toBe(50);
        expect($values[1]['price'])->toBe(100);
        expect($values[2]['price'])->toBe(150);
    });

    it('handles notBetween operator', function(): void {
        $products = [
            ['name' => 'Item A', 'price' => 10],
            ['name' => 'Item B', 'price' => 50],
            ['name' => 'Item C', 'price' => 100],
            ['name' => 'Item D', 'price' => 150],
            ['name' => 'Item E', 'price' => 200],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->notBetween('price', 50, 150)
            ->get();

        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['price'])->toBe(10);
        expect($values[1]['price'])->toBe(200);
    });

    it('combines multiple where conditions', function(): void {
        $users = [
            ['name' => 'Alice', 'age' => 25, 'city' => 'Berlin', 'active' => true],
            ['name' => 'Bob', 'age' => 30, 'city' => 'Munich', 'active' => true],
            ['name' => 'Charlie', 'age' => 35, 'city' => 'Berlin', 'active' => false],
            ['name' => 'David', 'age' => 28, 'city' => 'Hamburg', 'active' => true],
            ['name' => 'Eve', 'age' => 32, 'city' => 'Berlin', 'active' => true],
        ];

        $result = DataMapper::query()
            ->source('users', $users)
            ->where('city', '=', 'Berlin')
            ->where('active', '=', true)
            ->where('age', '>=', 30)
            ->get();

        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Eve');
    });

    it('handles empty results gracefully', function(): void {
        $data = [
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ];

        $result = DataMapper::query()
            ->source('users', $data)
            ->where('age', '>', 100)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    it('handles distinct operator', function(): void {
        $data = [
            ['category' => 'Electronics'],
            ['category' => 'Furniture'],
            ['category' => 'Electronics'],
            ['category' => 'Clothing'],
            ['category' => 'Furniture'],
            ['category' => 'Electronics'],
        ];

        $result = DataMapper::query()
            ->source('items', $data)
            ->distinct('category')
            ->get();

        expect($result)->toHaveCount(3);
        expect($result)->toContain(['category' => 'Electronics']);
        expect($result)->toContain(['category' => 'Furniture']);
        expect($result)->toContain(['category' => 'Clothing']);
    });

    it('handles orWhere conditions', function(): void {
        $users = [
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Charlie', 'age' => 35],
            ['name' => 'David', 'age' => 40],
        ];

        $result = DataMapper::query()
            ->source('users', $users)
            ->where('age', '=', 25)
            ->orWhere('age', '=', 40)
            ->get();

        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Alice');
        expect($values[1]['name'])->toBe('David');
    });

    it('handles complex nested where conditions', function(): void {
        $products = [
            ['name' => 'Laptop', 'price' => 1200, 'stock' => 5],
            ['name' => 'Mouse', 'price' => 25, 'stock' => 50],
            ['name' => 'Keyboard', 'price' => 75, 'stock' => 30],
            ['name' => 'Monitor', 'price' => 400, 'stock' => 8],
        ];

        $result = DataMapper::query()
            ->source('products', $products)
            ->where(function($query): void {
                $query->where('price', '>', 100)->where('stock', '<', 10);
            })
            ->get();

        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Laptop');
        expect($values[1]['name'])->toBe('Monitor');
    });

    it('handles skipNull option', function(): void {
        $data = [
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => null],
            ['name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        $result = DataMapper::query()
            ->source('users', $data)
            ->skipNull(true)
            ->get();

        $values = array_values($result);
        expect($values)->toHaveCount(3);
        // skipNull removes null values from output
        expect($values[0])->toHaveKey('email');
        expect($values[1])->toHaveKey('name');
        expect($values[2])->toHaveKey('email');
    });

    it('handles reindex option', function(): void {
        $data = [
            5 => ['name' => 'Alice'],
            10 => ['name' => 'Bob'],
            15 => ['name' => 'Charlie'],
        ];

        $result = DataMapper::query()
            ->source('users', $data)
            ->reindex(true)
            ->get();

        expect($result)->toHaveKey(0);
        expect($result)->toHaveKey(1);
        expect($result)->toHaveKey(2);
        expect($result[0]['name'])->toBe('Alice');
        expect($result[1]['name'])->toBe('Bob');
        expect($result[2]['name'])->toBe('Charlie');
    });
});

