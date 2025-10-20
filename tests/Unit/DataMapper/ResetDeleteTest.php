<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;

// Helper function to setup test data
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function setupProductsSource(): void
{
    test()->source = [
        'products' => [
            ['id' => 1, 'name' => 'Product A', 'status' => 'active', 'price' => 100],
            ['id' => 2, 'name' => 'Product B', 'status' => 'inactive', 'price' => 50],
            ['id' => 3, 'name' => 'Product C', 'status' => 'active', 'price' => 150],
            ['id' => 4, 'name' => 'Product D', 'status' => 'active', 'price' => 75],
        ],
    ];
}

describe('DataMapper Reset & Delete', function(): void {
    describe('reset()->all()', function(): void {
        beforeEach(fn() => setupProductsSource());
        it('resets entire template to original', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Add query WHERE
            $mapper->query('products.*')
                ->where('price', '>', 75)
                ->end();

            // Reset to original
            $mapper->reset()->all();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 3 items (status=active only, no price filter)
            expect($items)->toHaveCount(3);
            expect($items[0]['id'])->toBe(1);
            expect($items[1]['id'])->toBe(3);
            expect($items[2]['id'])->toBe(4);
        });

        it('resets template even after multiple modifications', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Multiple modifications
            $mapper->query('products.*')
                ->where('status', 'active')
                ->orderBy('price', 'DESC')
                ->limit(2)
                ->end();

            // Reset
            $mapper->reset()->all();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have all 4 items (no filters)
            expect($items)->toHaveCount(4);
        });
    });

    describe('reset()->template()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('is an alias for reset()->all()', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            $mapper->query('products.*')
                ->where('price', '>', 75)
                ->end();

            $mapper->reset()->template();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            expect($items)->toHaveCount(3);
        });
    });

    describe('reset()->where()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('resets WHERE to original template WHERE', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Add query WHERE
            $mapper->query('products.*')
                ->where('price', '>', 75)
                ->end();

            // Reset WHERE only
            $mapper->reset()->where();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 3 items (status=active only)
            expect($items)->toHaveCount(3);
        });

        it('removes WHERE if original template had no WHERE', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Add query WHERE
            $mapper->query('products.*')
                ->where('status', 'active')
                ->end();

            // Reset WHERE
            $mapper->reset()->where();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have all 4 items (no WHERE)
            expect($items)->toHaveCount(4);
        });
    });

    describe('reset()->orderBy()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('resets ORDER BY to original template ORDER BY', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'ORDER BY' => [
                            '{{ products.*.price }}' => 'DESC',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'price' => '{{ products.*.price }}',
                        ],
                    ],
                ]);

            // Add query ORDER BY
            $mapper->query('products.*')
                ->orderBy('price', 'ASC')
                ->end();

            // Reset ORDER BY
            $mapper->reset()->orderBy();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should be ordered by price DESC (original)
            expect($items[0]['id'])->toBe(3); // Price 150
            expect($items[1]['id'])->toBe(1); // Price 100
            expect($items[2]['id'])->toBe(4); // Price 75
        });
    });

    describe('reset()->limit()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('resets LIMIT to original template LIMIT', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'LIMIT' => 2,
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Add query LIMIT
            $mapper->query('products.*')
                ->limit(1)
                ->end();

            // Reset LIMIT
            $mapper->reset()->limit();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 2 items (original LIMIT)
            expect($items)->toHaveCount(2);
        });
    });

    describe('reset()->offset()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('resets OFFSET to original template OFFSET', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'OFFSET' => 1,
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Add query OFFSET
            $mapper->query('products.*')
                ->offset(2)
                ->end();

            // Reset OFFSET
            $mapper->reset()->offset();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should skip 1 item (original OFFSET)
            expect($items)->toHaveCount(2);
            expect($items[0]['id'])->toBe(3);
        });
    });

    describe('reset()->groupBy()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('resets GROUP BY to original template GROUP BY', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'GROUP BY' => [
                            'field' => '{{ products.*.status }}',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                            'status' => '{{ products.*.status }}',
                        ],
                    ],
                ]);

            // Modify with query (would add more GROUP BY if supported)
            // For now, just test reset works
            $mapper->reset()->groupBy();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 2 groups (active, inactive)
            expect($items)->toHaveCount(2);
        });
    });

    describe('Chaining reset methods', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('allows chaining multiple reset methods', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'ORDER BY' => [
                            '{{ products.*.price }}' => 'DESC',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Modify
            $mapper->query('products.*')
                ->where('price', '>', 75)
                ->orderBy('price', 'ASC')
                ->end();

            // Reset both
            $mapper->reset()->where()->orderBy();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 3 items (status=active)
            expect($items)->toHaveCount(3);
            // Should be ordered DESC (original)
            expect($items[0]['id'])->toBe(3);
        });

        it('allows ending reset chain with end()', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            $result = $mapper->reset()->all()->end();

            expect($result)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('delete()->all()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('deletes all operators from template', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'ORDER BY' => [
                            '{{ products.*.price }}' => 'DESC',
                        ],
                        'LIMIT' => 2,
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete all operators
            $mapper->delete()->all();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have all 4 items (no filters)
            expect($items)->toHaveCount(4);
        });
    });

    describe('delete()->where()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('deletes WHERE from template', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete WHERE
            $mapper->delete()->where();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have all 4 items (no WHERE)
            expect($items)->toHaveCount(4);
        });

        it('works even if template has no WHERE', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete WHERE (should not error)
            $mapper->delete()->where();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            expect($items)->toHaveCount(4);
        });
    });

    describe('delete()->orderBy()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('deletes ORDER BY from template', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'ORDER BY' => [
                            '{{ products.*.price }}' => 'DESC',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete ORDER BY
            $mapper->delete()->orderBy();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 3 items (WHERE still active)
            expect($items)->toHaveCount(3);
            // Should be in original order (no ORDER BY)
            expect($items[0]['id'])->toBe(1);
            expect($items[1]['id'])->toBe(3);
            expect($items[2]['id'])->toBe(4);
        });
    });

    describe('delete()->limit()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('deletes LIMIT from template', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'LIMIT' => 2,
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete LIMIT
            $mapper->delete()->limit();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 3 items (no LIMIT)
            expect($items)->toHaveCount(3);
        });
    });

    describe('delete()->offset()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('deletes OFFSET from template', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'OFFSET' => 1,
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete OFFSET
            $mapper->delete()->offset();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 3 items (no OFFSET)
            expect($items)->toHaveCount(3);
            expect($items[0]['id'])->toBe(1);
        });
    });

    describe('delete()->groupBy()', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('deletes GROUP BY from template', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'GROUP BY' => [
                            'field' => '{{ products.*.status }}',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete GROUP BY
            $mapper->delete()->groupBy();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have all 4 items (no GROUP BY)
            expect($items)->toHaveCount(4);
        });
    });

    describe('Chaining delete methods', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('allows chaining multiple delete methods', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'ORDER BY' => [
                            '{{ products.*.price }}' => 'DESC',
                        ],
                        'LIMIT' => 2,
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Delete multiple operators
            $mapper->delete()->where()->orderBy()->limit();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have all 4 items (no filters)
            expect($items)->toHaveCount(4);
        });

        it('allows ending delete chain with end()', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            $result = $mapper->delete()->all()->end();

            expect($result)->toBeInstanceOf(FluentDataMapper::class);
        });
    });

    describe('Combining reset and delete', function(): void {
        beforeEach(fn() => setupProductsSource());

        it('allows using reset and delete in sequence', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'items' => [
                        'WHERE' => [
                            '{{ products.*.status }}' => 'active',
                        ],
                        'ORDER BY' => [
                            '{{ products.*.price }}' => 'DESC',
                        ],
                        '*' => [
                            'id' => '{{ products.*.id }}',
                        ],
                    ],
                ]);

            // Modify
            $mapper->query('products.*')
                ->where('price', '>', 75)
                ->limit(1)
                ->end();

            // Reset WHERE (back to status=active only)
            $mapper->reset()->where();

            // Delete ORDER BY
            $mapper->delete()->orderBy();

            $result = $mapper->map();
            $items = $result->getTarget()['items'];

            // Should have 1 item (status=active AND price>75, LIMIT 1)
            expect($items)->toHaveCount(1);
            // Should be in original order (no ORDER BY)
            expect($items[0]['id'])->toBe(1);
        });
    });
});

