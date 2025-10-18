<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Support\WildcardOperators\GroupByOperator;

describe('GROUP BY Operator - Basic Grouping', function(): void {
    it('groups items by a single field', function(): void {
        $items = [
            0 => ['id' => 1, 'category' => 'electronics', 'price' => 100],
            1 => ['id' => 2, 'category' => 'furniture', 'price' => 200],
            2 => ['id' => 3, 'category' => 'electronics', 'price' => 150],
            3 => ['id' => 4, 'category' => 'furniture', 'price' => 250],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Should have 2 groups (electronics, furniture)
        expect($result)->toHaveCount(2);

        // First item of each group should be present
        expect($result[0]['category'])->toBe('electronics');
        expect($result[1]['category'])->toBe('furniture');
    });

    it('groups items by multiple fields', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'brand' => 'Sony', 'price' => 100],
            1 => ['category' => 'electronics', 'brand' => 'Samsung', 'price' => 120],
            2 => ['category' => 'electronics', 'brand' => 'Sony', 'price' => 150],
            3 => ['category' => 'furniture', 'brand' => 'IKEA', 'price' => 200],
            4 => ['category' => 'furniture', 'brand' => 'Sony', 'price' => 250],
        ];

        $sources = ['items' => $items];
        $config = [
            'fields' => [
                '{{ items.*.category }}',
                '{{ items.*.brand }}',
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Should have 4 groups: (electronics,Sony), (electronics,Samsung), (furniture,IKEA), (furniture,Sony)
        expect($result)->toHaveCount(4);
    });

    it('returns empty array when items are empty', function(): void {
        $result = GroupByOperator::group([], ['field' => '{{ items.*.category }}'], [], []);

        expect($result)->toBe([]);
    });

    it('accepts field with array for multiple fields', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'region' => 'north', 'price' => 100],
            1 => ['category' => 'electronics', 'region' => 'south', 'price' => 120],
            2 => ['category' => 'furniture', 'region' => 'north', 'price' => 200],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => [
                '{{ items.*.category }}',
                '{{ items.*.region }}',
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Should have 3 groups
        expect($result)->toHaveCount(3);
    });

    it('accepts fields with single string', function(): void {
        $items = [
            0 => ['id' => 1, 'category' => 'electronics', 'price' => 100],
            1 => ['id' => 2, 'category' => 'furniture', 'price' => 200],
            2 => ['id' => 3, 'category' => 'electronics', 'price' => 150],
        ];

        $sources = ['items' => $items];
        $config = [
            'fields' => '{{ items.*.category }}',
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Should have 2 groups
        expect($result)->toHaveCount(2);
        expect($result[0]['category'])->toBe('electronics');
        expect($result[1]['category'])->toBe('furniture');
    });

    it('returns items unchanged when config is empty', function(): void {
        $items = [
            0 => ['id' => 1],
            1 => ['id' => 2],
        ];

        $result = GroupByOperator::group($items, [], [], []);

        expect($result)->toBe($items);
    });

    it('returns items unchanged when no field is specified', function(): void {
        $items = [
            0 => ['id' => 1],
            1 => ['id' => 2],
        ];

        $config = [
            'aggregations' => [
                'count' => ['COUNT'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, [], []);

        expect($result)->toBe($items);
    });

    it('handles null values in grouping field', function(): void {
        $items = [
            0 => ['category' => 'electronics'],
            1 => ['category' => null],
            2 => ['category' => 'electronics'],
            3 => ['category' => null],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Should have 2 groups: electronics and null
        expect($result)->toHaveCount(2);
    });
});

describe('GROUP BY Operator - COUNT Aggregation', function(): void {
    it('counts items in each group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'price' => 100],
            1 => ['category' => 'furniture', 'price' => 200],
            2 => ['category' => 'electronics', 'price' => 150],
            3 => ['category' => 'electronics', 'price' => 120],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'item_count' => ['COUNT'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(2);

        // Electronics group should have count of 3
        $electronicsGroup = array_values(
            array_filter($result, fn(mixed $item): bool => is_array($item) && 'electronics' === $item['category'])
        )[0];
        expect($electronicsGroup['item_count'])->toBe(3);

        // Furniture group should have count of 1
        $furnitureGroup = array_values(
            array_filter($result, fn(mixed $item): bool => is_array($item) && 'furniture' === $item['category'])
        )[0];
        expect($furnitureGroup['item_count'])->toBe(1);
    });
});

describe('GROUP BY Operator - SUM Aggregation', function(): void {
    it('sums numeric values in each group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'price' => 100],
            1 => ['category' => 'furniture', 'price' => 200],
            2 => ['category' => 'electronics', 'price' => 150],
            3 => ['category' => 'electronics', 'price' => 50],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'total_price' => ['SUM', '{{ items.*.price }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(2);

        // Electronics group should have sum of 300
        $electronicsGroup = array_values(
            array_filter($result, fn(mixed $item): bool => is_array($item) && 'electronics' === $item['category'])
        )[0];
        expect($electronicsGroup['total_price'])->toBe(300);

        // Furniture group should have sum of 200
        $furnitureGroup = array_values(
            array_filter($result, fn(mixed $item): bool => is_array($item) && 'furniture' === $item['category'])
        )[0];
        expect($furnitureGroup['total_price'])->toBe(200);
    });

    it('handles float values in SUM', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 10.5],
            1 => ['category' => 'A', 'value' => 20.3],
            2 => ['category' => 'A', 'value' => 5.2],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'total' => ['SUM', '{{ items.*.value }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['total'])->toBe(36.0);
    });

    it('ignores non-numeric values in SUM', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 10],
            1 => ['category' => 'A', 'value' => 'invalid'],
            2 => ['category' => 'A', 'value' => 20],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'total' => ['SUM', '{{ items.*.value }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['total'])->toBe(30);
    });
});

describe('GROUP BY Operator - AVG Aggregation', function(): void {
    it('calculates average of numeric values', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'price' => 100],
            1 => ['category' => 'electronics', 'price' => 200],
            2 => ['category' => 'electronics', 'price' => 150],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'avg_price' => ['AVG', '{{ items.*.price }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['avg_price'])->toBe(150.0);
    });

    it('returns 0 for empty group in AVG', function(): void {
        $items = [];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'avg_price' => ['AVG', '{{ items.*.price }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toBe([]);
    });

    it('supports AVERAGE as alias for AVG', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 10],
            1 => ['category' => 'A', 'value' => 20],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'avg' => ['AVERAGE', '{{ items.*.value }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['avg'])->toBe(15.0);
    });
});

describe('GROUP BY Operator - MIN/MAX Aggregation', function(): void {
    it('finds minimum value in each group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'price' => 100],
            1 => ['category' => 'electronics', 'price' => 50],
            2 => ['category' => 'electronics', 'price' => 150],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'min_price' => ['MIN', '{{ items.*.price }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['min_price'])->toBe(50);
    });

    it('finds maximum value in each group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'price' => 100],
            1 => ['category' => 'electronics', 'price' => 50],
            2 => ['category' => 'electronics', 'price' => 150],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'max_price' => ['MAX', '{{ items.*.price }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['max_price'])->toBe(150);
    });
});

describe('GROUP BY Operator - FIRST/LAST Aggregation', function(): void {
    it('gets first value in each group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'name' => 'Laptop'],
            1 => ['category' => 'electronics', 'name' => 'Mouse'],
            2 => ['category' => 'electronics', 'name' => 'Keyboard'],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'first_product' => ['FIRST', '{{ items.*.name }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['first_product'])->toBe('Laptop');
    });

    it('gets last value in each group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'name' => 'Laptop'],
            1 => ['category' => 'electronics', 'name' => 'Mouse'],
            2 => ['category' => 'electronics', 'name' => 'Keyboard'],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'last_product' => ['LAST', '{{ items.*.name }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['last_product'])->toBe('Keyboard');
    });
});

describe('GROUP BY Operator - COLLECT Aggregation', function(): void {
    it('collects all values into an array', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'name' => 'Laptop'],
            1 => ['category' => 'electronics', 'name' => 'Mouse'],
            2 => ['category' => 'furniture', 'name' => 'Desk'],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'product_names' => ['COLLECT', '{{ items.*.name }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(2);

        $electronicsGroup = array_values(
            array_filter($result, fn(mixed $item): bool => is_array($item) && 'electronics' === $item['category'])
        )[0];
        expect($electronicsGroup['product_names'])->toBe(['Laptop', 'Mouse']);

        $furnitureGroup = array_values(
            array_filter($result, fn(mixed $item): bool => is_array($item) && 'furniture' === $item['category'])
        )[0];
        expect($furnitureGroup['product_names'])->toBe(['Desk']);
    });
});

describe('GROUP BY Operator - CONCAT Aggregation', function(): void {
    it('concatenates values with default separator', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'name' => 'Laptop'],
            1 => ['category' => 'electronics', 'name' => 'Mouse'],
            2 => ['category' => 'electronics', 'name' => 'Keyboard'],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'product_list' => ['CONCAT', '{{ items.*.name }}'],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['product_list'])->toBe('Laptop, Mouse, Keyboard');
    });

    it('concatenates values with custom separator', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'name' => 'Laptop'],
            1 => ['category' => 'electronics', 'name' => 'Mouse'],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'product_list' => ['CONCAT', '{{ items.*.name }}', ' | '],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result[0]['product_list'])->toBe('Laptop | Mouse');
    });
});

describe('GROUP BY Operator - Multiple Aggregations', function(): void {
    it('applies multiple aggregations to the same group', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'name' => 'Laptop', 'price' => 1000],
            1 => ['category' => 'electronics', 'name' => 'Mouse', 'price' => 50],
            2 => ['category' => 'electronics', 'name' => 'Keyboard', 'price' => 100],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'count' => ['COUNT'],
                'total_price' => ['SUM', '{{ items.*.price }}'],
                'avg_price' => ['AVG', '{{ items.*.price }}'],
                'min_price' => ['MIN', '{{ items.*.price }}'],
                'max_price' => ['MAX', '{{ items.*.price }}'],
                'first_product' => ['FIRST', '{{ items.*.name }}'],
                'last_product' => ['LAST', '{{ items.*.name }}'],
                'all_products' => ['COLLECT', '{{ items.*.name }}'],
                'product_list' => ['CONCAT', '{{ items.*.name }}', ', '],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[0]['count'])->toBe(3);
        expect($result[0]['total_price'])->toBe(1150);
        expect($result[0]['avg_price'])->toBe(1150 / 3);
        expect($result[0]['min_price'])->toBe(50);
        expect($result[0]['max_price'])->toBe(1000);
        expect($result[0]['first_product'])->toBe('Laptop');
        expect($result[0]['last_product'])->toBe('Keyboard');
        expect($result[0]['all_products'])->toBe(['Laptop', 'Mouse', 'Keyboard']);
        expect($result[0]['product_list'])->toBe('Laptop, Mouse, Keyboard');
    });
});

describe('GROUP BY Operator - HAVING Clause', function(): void {
    it('filters groups by aggregation result with > operator', function(): void {
        $items = [
            0 => ['category' => 'electronics', 'price' => 100],
            1 => ['category' => 'electronics', 'price' => 200],
            2 => ['category' => 'furniture', 'price' => 50],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'count' => ['COUNT'],
                'total' => ['SUM', '{{ items.*.price }}'],
            ],
            'HAVING' => [
                'count' => ['>', 1],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Only electronics group should remain (count = 2)
        expect($result)->toHaveCount(1);
        expect($result[0]['category'])->toBe('electronics');
    });

    it('filters groups with >= operator', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 100],
            1 => ['category' => 'A', 'value' => 200],
            2 => ['category' => 'B', 'value' => 50],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'total' => ['SUM', '{{ items.*.value }}'],
            ],
            'HAVING' => [
                'total' => ['>=', 300],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[0]['category'])->toBe('A');
        expect($result[0]['total'])->toBe(300);
    });

    it('filters groups with < operator', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 100],
            1 => ['category' => 'B', 'value' => 50],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'total' => ['SUM', '{{ items.*.value }}'],
            ],
            'HAVING' => [
                'total' => ['<', 75],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[0]['category'])->toBe('B');
    });

    it('filters groups with multiple HAVING conditions', function(): void {
        $items = [
            0 => ['category' => 'A', 'price' => 100],
            1 => ['category' => 'A', 'price' => 200],
            2 => ['category' => 'B', 'price' => 50],
            3 => ['category' => 'C', 'price' => 150],
            4 => ['category' => 'C', 'price' => 150],
            5 => ['category' => 'C', 'price' => 150],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'count' => ['COUNT'],
                'total' => ['SUM', '{{ items.*.price }}'],
            ],
            'HAVING' => [
                'count' => ['>', 1],
                'total' => ['>=', 300],
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        // Only A and C should remain
        expect($result)->toHaveCount(2);
    });

    it('supports direct value comparison in HAVING', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 10],
            1 => ['category' => 'A', 'value' => 10],
            2 => ['category' => 'B', 'value' => 5],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'count' => ['COUNT'],
            ],
            'HAVING' => [
                'count' => 2,  // Direct comparison
            ],
        ];

        $result = GroupByOperator::group($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[0]['category'])->toBe('A');
    });
});

describe('GROUP BY Operator - Error Handling', function(): void {
    it('throws exception for unknown aggregation function', function(): void {
        $items = [
            0 => ['category' => 'A', 'value' => 10],
        ];

        $sources = ['items' => $items];
        $config = [
            'field' => '{{ items.*.category }}',
            'aggregations' => [
                'result' => ['UNKNOWN_FUNCTION', '{{ items.*.value }}'],
            ],
        ];

        expect(fn(): array => GroupByOperator::group($items, $config, $sources, []))
            ->toThrow(InvalidArgumentException::class, 'Unknown aggregation function: UNKNOWN_FUNCTION');
    });
});
