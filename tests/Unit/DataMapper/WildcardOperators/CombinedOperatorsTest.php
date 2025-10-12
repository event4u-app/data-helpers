<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Combined Wildcard Operators', function(): void {
    beforeEach(function(): void {
        $this->sources = [
            'products' => [
                ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1200, 'stock' => 5, 'featured' => true],
                ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25, 'stock' => 50, 'featured' => false],
                ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 300, 'stock' => 10, 'featured' => true],
                ['id' => 4, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 150, 'stock' => 20, 'featured' => false],
                ['id' => 5, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400, 'stock' => 15, 'featured' => true],
                ['id' => 6, 'name' => 'Keyboard', 'category' => 'Electronics', 'price' => 75, 'stock' => 30, 'featured' => false],
                ['id' => 7, 'name' => 'Bookshelf', 'category' => 'Furniture', 'price' => 200, 'stock' => 8, 'featured' => true],
                ['id' => 8, 'name' => 'Lamp', 'category' => 'Furniture', 'price' => 50, 'stock' => 25, 'featured' => false],
            ],
        ];
    });

    it('combines WHERE + ORDER BY + LIMIT', function(): void {
        $template = [
            'top_electronics' => [
                'WHERE' => [
                    '{{ products.*.category }}' => 'Electronics',
                ],
                'ORDER BY' => [
                    '{{ products.*.price }}' => 'DESC',
                ],
                'LIMIT' => 2,
                '*' => [
                    'name' => '{{ products.*.name }}',
                    'price' => '{{ products.*.price }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['top_electronics'])->toHaveCount(2);
        expect($result['top_electronics'][0]['name'])->toBe('Laptop');
        expect($result['top_electronics'][0]['price'])->toBe(1200);
        expect($result['top_electronics'][1]['name'])->toBe('Monitor');
        expect($result['top_electronics'][1]['price'])->toBe(400);
    });

    it('combines WHERE + ORDER BY + OFFSET + LIMIT', function(): void {
        $template = [
            'paginated_electronics' => [
                'WHERE' => [
                    '{{ products.*.category }}' => 'Electronics',
                ],
                'ORDER BY' => [
                    '{{ products.*.price }}' => 'ASC',
                ],
                'OFFSET' => 1,
                'LIMIT' => 2,
                '*' => [
                    'name' => '{{ products.*.name }}',
                    'price' => '{{ products.*.price }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['paginated_electronics'])->toHaveCount(2);
        expect($result['paginated_electronics'][0]['name'])->toBe('Keyboard');
        expect($result['paginated_electronics'][0]['price'])->toBe(75);
        expect($result['paginated_electronics'][1]['name'])->toBe('Monitor');
        expect($result['paginated_electronics'][1]['price'])->toBe(400);
    });

    it('combines complex WHERE + ORDER BY + LIMIT', function(): void {
        $template = [
            'featured_affordable' => [
                'WHERE' => [
                    '{{ products.*.featured }}' => true,
                ],
                'ORDER BY' => [
                    '{{ products.*.price }}' => 'ASC',
                ],
                'LIMIT' => 3,
                '*' => [
                    'name' => '{{ products.*.name }}',
                    'category' => '{{ products.*.category }}',
                    'price' => '{{ products.*.price }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['featured_affordable'])->toHaveCount(3);
        expect($result['featured_affordable'][0]['name'])->toBe('Bookshelf');
        expect($result['featured_affordable'][1]['name'])->toBe('Desk');
        expect($result['featured_affordable'][2]['name'])->toBe('Monitor');
    });

    it('combines ORDER BY multiple fields + OFFSET + LIMIT', function(): void {
        $template = [
            'sorted_products' => [
                'ORDER BY' => [
                    '{{ products.*.category }}' => 'ASC',
                    '{{ products.*.price }}' => 'DESC',
                ],
                'OFFSET' => 2,
                'LIMIT' => 3,
                '*' => [
                    'name' => '{{ products.*.name }}',
                    'category' => '{{ products.*.category }}',
                    'price' => '{{ products.*.price }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['sorted_products'])->toHaveCount(3);
        // After sorting by category ASC, price DESC:
        // Electronics: Laptop(1200), Monitor(400), Keyboard(75), Mouse(25)
        // Furniture: Desk(300), Bookshelf(200), Chair(150), Lamp(50)
        // OFFSET 2, LIMIT 3 should give: Keyboard, Mouse, Desk
        expect($result['sorted_products'][0]['name'])->toBe('Keyboard');
        expect($result['sorted_products'][1]['name'])->toBe('Mouse');
        expect($result['sorted_products'][2]['name'])->toBe('Desk');
    });

    it('handles empty result after WHERE', function(): void {
        $template = [
            'nonexistent' => [
                'WHERE' => [
                    '{{ products.*.category }}' => 'Nonexistent',
                ],
                'ORDER BY' => [
                    '{{ products.*.price }}' => 'DESC',
                ],
                'LIMIT' => 5,
                '*' => [
                    'name' => '{{ products.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['nonexistent'])->toHaveCount(0);
    });

    it('handles LIMIT larger than filtered results', function(): void {
        $template = [
            'all_furniture' => [
                'WHERE' => [
                    '{{ products.*.category }}' => 'Furniture',
                ],
                'LIMIT' => 100,
                '*' => [
                    'name' => '{{ products.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['all_furniture'])->toHaveCount(4);
    });

    it('handles OFFSET larger than filtered results', function(): void {
        $template = [
            'offset_furniture' => [
                'WHERE' => [
                    '{{ products.*.category }}' => 'Furniture',
                ],
                'OFFSET' => 100,
                '*' => [
                    'name' => '{{ products.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['offset_furniture'])->toHaveCount(0);
    });

    it('applies operators in correct order', function(): void {
        // Order should be: WHERE -> ORDER BY -> OFFSET -> LIMIT
        $template = [
            'ordered_operations' => [
                'WHERE' => [
                    '{{ products.*.featured }}' => true,
                ],
                'ORDER BY' => [
                    '{{ products.*.price }}' => 'DESC',
                ],
                'OFFSET' => 1,
                'LIMIT' => 2,
                '*' => [
                    'name' => '{{ products.*.name }}',
                    'price' => '{{ products.*.price }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        // Featured products: Laptop(1200), Desk(300), Monitor(400), Bookshelf(200)
        // After ORDER BY DESC: Laptop(1200), Monitor(400), Desk(300), Bookshelf(200)
        // After OFFSET 1: Monitor(400), Desk(300), Bookshelf(200)
        // After LIMIT 2: Monitor(400), Desk(300)
        expect($result['ordered_operations'])->toHaveCount(2);
        expect($result['ordered_operations'][0]['name'])->toBe('Monitor');
        expect($result['ordered_operations'][1]['name'])->toBe('Desk');
    });
});

