<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter;

describe('DataFilter Documentation Examples', function(): void {
    it('validates README DataFilter example', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25],
            ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 300],
            ['id' => 4, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400],
        ];

        $result = DataFilter::query($products)
            ->where('category', '=', 'Electronics')
            ->where('price', '>', 100)
            ->orderBy('price', 'DESC')
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Laptop');
        expect($values[1]['name'])->toBe('Monitor');
    })->group('docs', 'readme', 'data-filter');

    it('validates simple where condition', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 100)
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(1);
        expect($values[0]['name'])->toBe('Laptop');
    })->group('docs', 'data-filter', 'where');

    it('validates orderBy', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->orderBy('price', 'DESC')
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values[0]['name'])->toBe('Laptop');
        expect($values[1]['name'])->toBe('Keyboard');
        expect($values[2]['name'])->toBe('Mouse');
    })->group('docs', 'data-filter', 'order');

    it('validates limit', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->orderBy('price', 'DESC')
            ->limit(2)
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Laptop');
        expect($values[1]['name'])->toBe('Keyboard');
    })->group('docs', 'data-filter', 'limit');

    it('validates offset', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $result = DataFilter::query($products)
            ->orderBy('price', 'DESC')
            ->offset(1)
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('Keyboard');
        expect($values[1]['name'])->toBe('Mouse');
    })->group('docs', 'data-filter', 'offset');

    it('validates first method', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 100)
            ->first();

        expect($result)->toBeArray();
        expect($result['name'])->toBe('Laptop');
    })->group('docs', 'data-filter', 'first');

    it('validates count method', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75],
        ];

        $count = DataFilter::query($products)
            ->where('price', '>', 50)
            ->count();

        expect($count)->toBe(2);
    })->group('docs', 'data-filter', 'count');

    it('validates multiple where conditions', function(): void {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 1200, 'stock' => 5],
            ['id' => 2, 'name' => 'Mouse', 'price' => 25, 'stock' => 50],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 75, 'stock' => 30],
            ['id' => 4, 'name' => 'Monitor', 'price' => 400, 'stock' => 8],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 50)
            ->where('stock', '<', 20)
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
    })->group('docs', 'data-filter', 'multiple-where');

    it('validates comparison operators', function(): void {
        $products = [
            ['id' => 1, 'price' => 100],
            ['id' => 2, 'price' => 200],
            ['id' => 3, 'price' => 300],
        ];

        // Greater than
        $result = DataFilter::query($products)->where('price', '>', 150)->get();
        expect(array_values($result))->toHaveCount(2);

        // Less than
        $result = DataFilter::query($products)->where('price', '<', 150)->get();
        expect(array_values($result))->toHaveCount(1);

        // Equals
        $result = DataFilter::query($products)->where('price', '=', 200)->get();
        expect(array_values($result))->toHaveCount(1);

        // Greater than or equal
        $result = DataFilter::query($products)->where('price', '>=', 200)->get();
        expect(array_values($result))->toHaveCount(2);

        // Less than or equal
        $result = DataFilter::query($products)->where('price', '<=', 200)->get();
        expect(array_values($result))->toHaveCount(2);
    })->group('docs', 'data-filter', 'operators');

    it('validates empty result', function(): void {
        $products = [
            ['id' => 1, 'price' => 100],
        ];

        $result = DataFilter::query($products)
            ->where('price', '>', 1000)
            ->get();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    })->group('docs', 'data-filter', 'edge-cases');

    it('validates chaining methods', function(): void {
        $products = [
            ['id' => 1, 'name' => 'A', 'price' => 100, 'category' => 'Electronics'],
            ['id' => 2, 'name' => 'B', 'price' => 200, 'category' => 'Electronics'],
            ['id' => 3, 'name' => 'C', 'price' => 150, 'category' => 'Furniture'],
            ['id' => 4, 'name' => 'D', 'price' => 300, 'category' => 'Electronics'],
        ];

        $result = DataFilter::query($products)
            ->where('category', '=', 'Electronics')
            ->where('price', '>', 100)
            ->orderBy('price', 'ASC')
            ->limit(2)
            ->get();

        expect($result)->toBeArray();
        $values = array_values($result);
        expect($values)->toHaveCount(2);
        expect($values[0]['name'])->toBe('B');
        expect($values[1]['name'])->toBe('D');
    })->group('docs', 'data-filter', 'chaining');
});
