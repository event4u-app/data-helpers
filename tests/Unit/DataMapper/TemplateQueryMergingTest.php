<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Template Query Merging', function (): void {
    describe('Original Template Storage', function (): void {
        it('stores original template when template() is called', function (): void {
            $template = [
                'items' => [
                    '*' => [
                        'id' => '{{ products.*.id }}',
                        'name' => '{{ products.*.name }}',
                    ],
                ],
            ];

            $mapper = DataMapper::source([])
                ->template($template);

            expect($mapper->getOriginalTemplate())->toBe($template);
        });

        it('preserves original template when extendTemplate() is called', function (): void {
            $originalTemplate = [
                'items' => [
                    '*' => [
                        'id' => '{{ products.*.id }}',
                    ],
                ],
            ];

            $mapper = DataMapper::source([])
                ->template($originalTemplate)
                ->extendTemplate([
                    'items' => [
                        '*' => [
                            'name' => '{{ products.*.name }}',
                        ],
                    ],
                ]);

            expect($mapper->getOriginalTemplate())->toBe($originalTemplate);
        });

        it('copies original template when copy() is called', function (): void {
            $template = [
                'items' => [
                    '*' => [
                        'id' => '{{ products.*.id }}',
                    ],
                ],
            ];

            $mapper = DataMapper::source([])
                ->template($template);

            $copy = $mapper->copy();

            expect($copy->getOriginalTemplate())->toBe($template);
        });

        it('only stores first template as original', function (): void {
            $firstTemplate = [
                'items' => [
                    '*' => [
                        'id' => '{{ products.*.id }}',
                    ],
                ],
            ];

            $secondTemplate = [
                'items' => [
                    '*' => [
                        'name' => '{{ products.*.name }}',
                    ],
                ],
            ];

            $mapper = DataMapper::source([])
                ->template($firstTemplate)
                ->template($secondTemplate);

            expect($mapper->getOriginalTemplate())->toBe($firstTemplate);
        });
    });

    describe('WHERE Condition Merging', function (): void {
        it('merges template WHERE with query WHERE (AND logic)', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'Product A', 'status' => 'active', 'price' => 100],
                    ['id' => 2, 'name' => 'Product B', 'status' => 'active', 'price' => 50],
                    ['id' => 3, 'name' => 'Product C', 'status' => 'inactive', 'price' => 150],
                    ['id' => 4, 'name' => 'Product D', 'status' => 'active', 'price' => 200],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->where('price', '>', 75)
                    ->end()
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            // Should only have Product A (status=active AND price>75) and Product D (status=active AND price>75)
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(1);
            expect($items[0]['name'])->toBe('Product A');
            expect($items[1]['id'])->toBe(4);
            expect($items[1]['name'])->toBe('Product D');
        });

        it('merges multiple template WHERE conditions with query WHERE', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'status' => 'active', 'price' => 100, 'stock' => 10],
                    ['id' => 2, 'status' => 'active', 'price' => 50, 'stock' => 5],
                    ['id' => 3, 'status' => 'active', 'price' => 150, 'stock' => 0],
                    ['id' => 4, 'status' => 'inactive', 'price' => 200, 'stock' => 20],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->where('price', '>', 75)
                    ->end()
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                            '{{ products.*.stock }}' => ['>', 0],
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'price' => '{{ products.*.price }}',
                            'stock' => '{{ products.*.stock }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            // Should only have Product 1 (status=active AND stock>0 AND price>75)
            expect($items)->toHaveCount(1);
            expect($items[0]['id'])->toBe(1);
        });

        it('works with query WHERE only (no template WHERE)', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'status' => 'active'],
                    ['id' => 2, 'status' => 'inactive'],
                    ['id' => 3, 'status' => 'active'],
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
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(1);
            expect($items[1]['id'])->toBe(3);
        });

        it('works with template WHERE only (no query WHERE)', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'status' => 'active'],
                    ['id' => 2, 'status' => 'inactive'],
                    ['id' => 3, 'status' => 'active'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(1);
            expect($items[1]['id'])->toBe(3);
        });
    });

    describe('ORDER BY Merging', function (): void {
        it('merges template ORDER BY with query ORDER BY', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'B Product', 'price' => 100],
                    ['id' => 2, 'name' => 'A Product', 'price' => 200],
                    ['id' => 3, 'name' => 'C Product', 'price' => 150],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->orderBy('price', 'ASC')
                    ->end()
                ->template([
                    'items' => [
                        'ORDER BY' => [
                            '{{ products.*.name }}' => 'ASC',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'name' => '{{ products.*.name }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ])
                ->map();

            $items = $result->getTarget()['items'];
            // Should be ordered by name ASC first, then by price ASC
            expect($items)->toHaveCount(3);
            expect($items[0]['name'])->toBe('A Product');
            expect($items[1]['name'])->toBe('B Product');
            expect($items[2]['name'])->toBe('C Product');
        });
    });

    describe('Result Does Not Contain Operator Keys', function (): void {
        it('does not include WHERE in result', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'status' => 'active'],
                    ['id' => 2, 'status' => 'inactive'],
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
                        ],
                    ],
                ])
                ->map();

            $target = $result->getTarget();
            expect($target)->toHaveKey('items');
            expect($target)->not->toHaveKey('WHERE');
            expect($target['items'])->not->toHaveKey('WHERE');
        });

        it('does not include ORDER BY in result', function (): void {
            $source = [
                'products' => [
                    ['id' => 1, 'name' => 'B'],
                    ['id' => 2, 'name' => 'A'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->query('products.*')
                    ->orderBy('name', 'ASC')
                    ->end()
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ])
                ->map();

            $target = $result->getTarget();
            expect($target)->not->toHaveKey('ORDER BY');
            expect($target['items'])->not->toHaveKey('ORDER BY');
        });

        it('does not include LIMIT in result', function (): void {
            $source = [
                'products' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
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
                        ],
                    ],
                ])
                ->map();

            $target = $result->getTarget();
            expect($target)->not->toHaveKey('LIMIT');
            expect($target['items'])->not->toHaveKey('LIMIT');
        });
    });
});

