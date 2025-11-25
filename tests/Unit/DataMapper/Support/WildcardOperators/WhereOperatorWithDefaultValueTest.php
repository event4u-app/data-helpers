<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('WHERE Operator with Default Values (??)', function(): void {
    beforeEach(function(): void {
        $this->sources = [
            'positions' => [
                [
                    'id' => 1,
                    'name' => 'Position 1',
                    'record_state' => '1',  // Should be filtered out
                ],
                [
                    'id' => 2,
                    'name' => 'Position 2',
                    'record_state' => '0',  // Should be included
                ],
                [
                    'id' => 3,
                    'name' => 'Position 3',
                    // record_state missing - should default to "0" and be included
                ],
                [
                    'id' => 4,
                    'name' => 'Position 4',
                    'record_state' => null,  // Should default to "0" and be included
                ],
                [
                    'id' => 5,
                    'name' => 'Position 5',
                    'record_state' => '1',  // Should be filtered out
                ],
            ],
        ];
    });

    it('filters with default value when field is missing', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    '{{ positions.*.record_state ?? "0" }}' => ['!=', '1'],
                ],
                '*' => [
                    'id' => '{{ positions.*.id }}',
                    'name' => '{{ positions.*.name }}',
                    'record_state' => '{{ positions.*.record_state ?? "0" }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result['filtered_positions'])->toHaveCount(3)
            ->and($result['filtered_positions'][0]['id'])->toBe(2)
            ->and($result['filtered_positions'][0]['record_state'])->toBe('0')
            ->and($result['filtered_positions'][1]['id'])->toBe(3)
            ->and($result['filtered_positions'][1]['record_state'])->toBe('0')
            ->and($result['filtered_positions'][2]['id'])->toBe(4)
            ->and($result['filtered_positions'][2]['record_state'])->toBe('0');
    });

    it('filters with default value in AND condition', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'AND' => [
                        '{{ positions.*.record_state ?? "0" }}' => ['!=', '1'],
                        '{{ positions.*.id }}' => ['>', 2],
                    ],
                ],
                '*' => [
                    'id' => '{{ positions.*.id }}',
                    'name' => '{{ positions.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result['filtered_positions'])->toHaveCount(2)
            ->and($result['filtered_positions'][0]['id'])->toBe(3)
            ->and($result['filtered_positions'][1]['id'])->toBe(4);
    });

    it('filters with default value in OR condition', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'OR' => [
                        '{{ positions.*.record_state ?? "0" }}' => '0',
                        '{{ positions.*.id }}' => 1,
                    ],
                ],
                '*' => [
                    'id' => '{{ positions.*.id }}',
                    'name' => '{{ positions.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Should include: id=1 (matches id condition), id=2 (record_state=0), 
        // id=3 (record_state defaults to 0), id=4 (record_state defaults to 0)
        expect($result['filtered_positions'])->toHaveCount(4);
    });

    it('uses default value with numeric type', function(): void {
        $sources = [
            'items' => [
                ['id' => 1, 'status' => 1],
                ['id' => 2, 'status' => 0],
                ['id' => 3],  // status missing
                ['id' => 4, 'status' => null],
            ],
        ];

        $template = [
            'active_items' => [
                'WHERE' => [
                    '{{ items.*.status ?? "0" }}' => '0',
                ],
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result['active_items'])->toHaveCount(3)
            ->and($result['active_items'][0]['id'])->toBe(2)
            ->and($result['active_items'][1]['id'])->toBe(3)
            ->and($result['active_items'][2]['id'])->toBe(4);
    });

    it('handles default value with comparison operators', function(): void {
        $sources = [
            'products' => [
                ['id' => 1, 'price' => 100],
                ['id' => 2, 'price' => 50],
                ['id' => 3],  // price missing, defaults to 0
                ['id' => 4, 'price' => null],  // price null, defaults to 0
            ],
        ];

        $template = [
            'expensive_products' => [
                'WHERE' => [
                    '{{ products.*.price ?? "0" }}' => ['>', 40],
                ],
                '*' => [
                    'id' => '{{ products.*.id }}',
                    'price' => '{{ products.*.price ?? "0" }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result['expensive_products'])->toHaveCount(2)
            ->and($result['expensive_products'][0]['id'])->toBe(1)
            ->and($result['expensive_products'][0]['price'])->toBe(100)
            ->and($result['expensive_products'][1]['id'])->toBe(2)
            ->and($result['expensive_products'][1]['price'])->toBe(50);
    });

    it('handles default value with single quotes', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    "{{ positions.*.record_state ?? '0' }}" => ['!=', '1'],
                ],
                '*' => [
                    'id' => '{{ positions.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result['filtered_positions'])->toHaveCount(3);
    });

    it('handles default value without quotes', function(): void {
        $sources = [
            'items' => [
                ['id' => 1, 'count' => 5],
                ['id' => 2],  // count missing
                ['id' => 3, 'count' => null],
            ],
        ];

        $template = [
            'items_with_count' => [
                'WHERE' => [
                    '{{ items.*.count ?? 0 }}' => ['>', 0],
                ],
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result['items_with_count'])->toHaveCount(1)
            ->and($result['items_with_count'][0]['id'])->toBe(1);
    });
});
