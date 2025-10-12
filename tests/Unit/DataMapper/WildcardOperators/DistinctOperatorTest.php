<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Support\WildcardOperators\DistinctOperator;

describe('DISTINCT Operator', function(): void {
    it('removes duplicate items based on a field', function(): void {
        $items = [
            0 => ['id' => 1, 'category' => 'electronics', 'name' => 'Laptop'],
            1 => ['id' => 2, 'category' => 'electronics', 'name' => 'Mouse'],
            2 => ['id' => 3, 'category' => 'furniture', 'name' => 'Desk'],
            3 => ['id' => 4, 'category' => 'electronics', 'name' => 'Keyboard'],
            4 => ['id' => 5, 'category' => 'furniture', 'name' => 'Chair'],
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.category }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        // Should keep first occurrence of each category
        expect($result)->toHaveCount(2);
        expect($result[0]['category'])->toBe('electronics');
        expect($result[2]['category'])->toBe('furniture');
    });

    it('removes duplicate items based on entire item when config is true', function(): void {
        $items = [
            0 => ['id' => 1, 'name' => 'Laptop'],
            1 => ['id' => 2, 'name' => 'Mouse'],
            2 => ['id' => 1, 'name' => 'Laptop'], // Duplicate
            3 => ['id' => 3, 'name' => 'Keyboard'],
        ];

        $result = DistinctOperator::filter($items, true, [], []);

        expect($result)->toHaveCount(3);
        expect($result)->toHaveKeys([0, 1, 3]);
    });

    it('returns all items when no duplicates exist', function(): void {
        $items = [
            0 => ['id' => 1, 'category' => 'electronics'],
            1 => ['id' => 2, 'category' => 'furniture'],
            2 => ['id' => 3, 'category' => 'clothing'],
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.category }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(3);
    });

    it('handles empty array', function(): void {
        $result = DistinctOperator::filter([], '{{ items.*.category }}', [], []);

        expect($result)->toBe([]);
    });

    it('preserves original indices', function(): void {
        $items = [
            5 => ['category' => 'A'],
            10 => ['category' => 'B'],
            15 => ['category' => 'A'], // Duplicate
            20 => ['category' => 'C'],
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.category }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveKeys([5, 10, 20]);
    });

    it('handles numeric field values', function(): void {
        $items = [
            0 => ['price' => 100],
            1 => ['price' => 200],
            2 => ['price' => 100], // Duplicate
            3 => ['price' => 300],
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.price }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(3);
        expect($result)->toHaveKeys([0, 1, 3]);
    });

    it('handles null values', function(): void {
        $items = [
            0 => ['status' => 'active'],
            1 => ['status' => null],
            2 => ['status' => null], // Duplicate null
            3 => ['status' => 'inactive'],
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.status }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(3);
        expect($result)->toHaveKeys([0, 1, 3]);
    });

    it('returns items unchanged when config is invalid', function(): void {
        $items = [
            0 => ['id' => 1],
            1 => ['id' => 2],
        ];

        $result = DistinctOperator::filter($items, 123, [], []);

        expect($result)->toBe($items);
    });

    it('works with template expressions in config', function(): void {
        $items = [
            0 => ['type' => 'A'],
            1 => ['type' => 'B'],
            2 => ['type' => 'A'],
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.type }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
    });

    it('handles complex nested values', function(): void {
        $items = [
            0 => ['data' => ['nested' => 'value1']],
            1 => ['data' => ['nested' => 'value2']],
            2 => ['data' => ['nested' => 'value1']], // Duplicate
        ];

        $sources = ['items' => $items];
        $config = '{{ items.*.data.nested }}';

        $result = DistinctOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys([0, 1]);
    });
});

