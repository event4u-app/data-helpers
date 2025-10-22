<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('MapperQuery Integration', function(): void {
    describe('WHERE operator integration', function(): void {
        it('applies WHERE condition to wildcard mapping', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A', 'price' => 100, 'status' => 'active'],
                    ['id' => 2, 'name' => 'Product B', 'price' => 200, 'status' => 'inactive'],
                    ['id' => 3, 'name' => 'Product C', 'price' => 150, 'status' => 'active'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->where('status', 'active')
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(1);
            expect($items[1]['id'])->toBe(3);
        });

        it('applies multiple WHERE conditions', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A', 'price' => 100, 'status' => 'active'],
                    ['id' => 2, 'name' => 'Product B', 'price' => 200, 'status' => 'active'],
                    ['id' => 3, 'name' => 'Product C', 'price' => 150, 'status' => 'active'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->where('status', 'active')
                    ->where('price', '>', 120)
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(2);
            expect($items[1]['id'])->toBe(3);
        });
    });

    describe('ORDER BY operator integration', function(): void {
        it('applies ORDER BY to wildcard mapping', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A', 'price' => 150],
                    ['id' => 2, 'name' => 'Product B', 'price' => 100],
                    ['id' => 3, 'name' => 'Product C', 'price' => 200],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->orderBy('price', 'ASC')
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(3);
            expect($items[0]['price'])->toBe(100);
            expect($items[1]['price'])->toBe(150);
            expect($items[2]['price'])->toBe(200);
        });

        it('applies ORDER BY DESC', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A', 'price' => 150],
                    ['id' => 2, 'name' => 'Product B', 'price' => 100],
                    ['id' => 3, 'name' => 'Product C', 'price' => 200],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->orderBy('price', 'DESC')
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(3);
            expect($items[0]['price'])->toBe(200);
            expect($items[1]['price'])->toBe(150);
            expect($items[2]['price'])->toBe(100);
        });
    });

    describe('LIMIT operator integration', function(): void {
        it('applies LIMIT to wildcard mapping', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A'],
                    ['id' => 2, 'name' => 'Product B'],
                    ['id' => 3, 'name' => 'Product C'],
                    ['id' => 4, 'name' => 'Product D'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->limit(2)
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(1);
            expect($items[1]['id'])->toBe(2);
        });
    });

    describe('OFFSET operator integration', function(): void {
        it('applies OFFSET to wildcard mapping', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A'],
                    ['id' => 2, 'name' => 'Product B'],
                    ['id' => 3, 'name' => 'Product C'],
                    ['id' => 4, 'name' => 'Product D'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->offset(2)
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(3);
            expect($items[1]['id'])->toBe(4);
        });
    });

    describe('Combined operators', function(): void {
        it('applies WHERE + ORDER BY + LIMIT', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A', 'price' => 150, 'status' => 'active'],
                    ['id' => 2, 'name' => 'Product B', 'price' => 100, 'status' => 'active'],
                    ['id' => 3, 'name' => 'Product C', 'price' => 200, 'status' => 'active'],
                    ['id' => 4, 'name' => 'Product D', 'price' => 120, 'status' => 'inactive'],
                    ['id' => 5, 'name' => 'Product E', 'price' => 180, 'status' => 'active'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->where('status', 'active')
                    ->orderBy('price', 'DESC')
                    ->limit(2)
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(3); // Price 200
            expect($items[1]['id'])->toBe(5); // Price 180
        });
    });

    describe('GROUP BY operator integration', function(): void {
        it('applies GROUP BY to wildcard mapping', function(): void {
            $source = [
                'products' => [
                    ['id' => 1, 'category' => 'Electronics', 'price' => 100],
                    ['id' => 2, 'category' => 'Electronics', 'price' => 200],
                    ['id' => 3, 'category' => 'Books', 'price' => 50],
                    ['id' => 4, 'category' => 'Books', 'price' => 30],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->groupBy('category')
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'category' => '{{ products.*.category }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            // GROUP BY groups items by category
            // Without aggregation, it returns first item of each group
            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['category'])->toBe('Electronics');
            expect($items[1]['category'])->toBe('Books');
        });
    });
});
