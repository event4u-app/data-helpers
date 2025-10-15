<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Wildcard ORDER BY', function(): void {
    beforeEach(function(): void {
        $this->sources = [
            'positions' => [
                ['pos_number' => '1.3', 'type' => 'gravel', 'quantity' => 100, 'priority' => 2],
                ['pos_number' => '1.1', 'type' => 'sand', 'quantity' => 50, 'priority' => 1],
                ['pos_number' => '1.2', 'type' => 'gravel', 'quantity' => 120, 'priority' => 3],
                ['pos_number' => '1.4', 'type' => 'sand', 'quantity' => 80, 'priority' => 1],
            ],
        ];
    });

    it('sorts by single field ascending', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER BY' => [
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'type' => '{{ positions.*.type }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1');
        expect($result['sorted_positions'][1]['number'])->toBe('1.2');
        expect($result['sorted_positions'][2]['number'])->toBe('1.3');
        expect($result['sorted_positions'][3]['number'])->toBe('1.4');
    });

    it('sorts by single field descending', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER BY' => [
                    '{{ positions.*.pos_number }}' => 'DESC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.4');
        expect($result['sorted_positions'][1]['number'])->toBe('1.3');
        expect($result['sorted_positions'][2]['number'])->toBe('1.2');
        expect($result['sorted_positions'][3]['number'])->toBe('1.1');
    });

    it('sorts by multiple fields', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER BY' => [
                    '{{ positions.*.priority }}' => 'ASC',
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'priority' => '{{ positions.*.priority }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1'); // priority 1
        expect($result['sorted_positions'][1]['number'])->toBe('1.4'); // priority 1
        expect($result['sorted_positions'][2]['number'])->toBe('1.3'); // priority 2
        expect($result['sorted_positions'][3]['number'])->toBe('1.2'); // priority 3
    });

    it('sorts with mixed ASC/DESC', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER BY' => [
                    '{{ positions.*.priority }}' => 'ASC',
                    '{{ positions.*.quantity }}' => 'DESC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'priority' => '{{ positions.*.priority }}',
                    'quantity' => '{{ positions.*.quantity }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.4'); // priority 1, quantity 80
        expect($result['sorted_positions'][1]['number'])->toBe('1.1'); // priority 1, quantity 50
        expect($result['sorted_positions'][2]['number'])->toBe('1.3'); // priority 2, quantity 100
        expect($result['sorted_positions'][3]['number'])->toBe('1.2'); // priority 3, quantity 120
    });

    it('handles case-insensitive ASC/DESC', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER BY' => [
                    '{{ positions.*.pos_number }}' => 'asc',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1');
    });

    it('combines WHERE and ORDER BY', function(): void {
        $template = [
            'filtered_sorted_positions' => [
                'WHERE' => [
                    '{{ positions.*.type }}' => 'gravel',
                ],
                'order by' => [
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'type' => '{{ positions.*.type }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_sorted_positions'])->toHaveCount(2);
        expect($result['filtered_sorted_positions'][0]['number'])->toBe('1.2');
        expect($result['filtered_sorted_positions'][1]['number'])->toBe('1.3');
    });

    it('works without ORDER BY (backward compatibility)', function(): void {
        $template = [
            'positions' => [
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['positions'])->toHaveCount(4);
    });

    it('sorts by string field alphabetically', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER_BY' => [
                    '{{ positions.*.type }}' => 'ASC',
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'type' => '{{ positions.*.type }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['type'])->toBe('gravel');
        expect($result['sorted_positions'][1]['type'])->toBe('gravel');
        expect($result['sorted_positions'][2]['type'])->toBe('sand');
        expect($result['sorted_positions'][3]['type'])->toBe('sand');
    });

    it('handles numeric sorting correctly', function(): void {
        $sources = [
            'items' => [
                ['id' => 10],
                ['id' => 2],
                ['id' => 100],
                ['id' => 20],
            ],
        ];

        $template = [
            'sorted_items' => [
                'ORDER' => [
                    '{{ items.*.id }}' => 'ASC',
                ],
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_items'])->toHaveCount(4);
        expect($result['sorted_items'][0]['id'])->toBe(2);
        expect($result['sorted_items'][1]['id'])->toBe(10);
        expect($result['sorted_items'][2]['id'])->toBe(20);
        expect($result['sorted_items'][3]['id'])->toBe(100);
    });

    it('handles null values in sorting', function(): void {
        $sources = [
            'items' => [
                ['name' => 'Charlie', 'priority' => null],
                ['name' => 'Alice', 'priority' => 1],
                ['name' => 'Bob', 'priority' => null],
                ['name' => 'David', 'priority' => 2],
            ],
        ];

        $template = [
            'sorted_items' => [
                'order' => [
                    '{{ items.*.priority }}' => 'ASC',
                    '{{ items.*.name }}' => 'ASC',
                ],
                '*' => [
                    'name' => '{{ items.*.name }}',
                    'priority' => '{{ items.*.priority }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_items'])->toHaveCount(4);
        // Nulls should come first in ASC order
        expect($result['sorted_items'][0]['name'])->toBe('Bob');
        expect($result['sorted_items'][1]['name'])->toBe('Charlie');
        expect($result['sorted_items'][2]['name'])->toBe('Alice');
        expect($result['sorted_items'][3]['name'])->toBe('David');
    });

    it('recognizes ORDER_BY with underscore', function(): void {
        $template = [
            'sorted_positions' => [
                'ORDER_BY' => [
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1');
    });

    it('recognizes lowercase order by', function(): void {
        $template = [
            'sorted_positions' => [
                'order by' => [
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1');
    });

    it('recognizes lowercase order_by with underscore', function(): void {
        $template = [
            'sorted_positions' => [
                'order_by' => [
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1');
    });

    it('recognizes short form order', function(): void {
        $template = [
            'sorted_positions' => [
                'order' => [
                    '{{ positions.*.pos_number }}' => 'ASC',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['sorted_positions'])->toHaveCount(4);
        expect($result['sorted_positions'][0]['number'])->toBe('1.1');
    });
});

