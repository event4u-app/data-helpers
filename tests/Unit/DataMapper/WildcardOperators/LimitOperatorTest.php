<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('LIMIT Operator', function(): void {
    beforeEach(function(): void {
        $this->sources = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
                ['id' => 3, 'name' => 'Item 3'],
                ['id' => 4, 'name' => 'Item 4'],
                ['id' => 5, 'name' => 'Item 5'],
            ],
        ];
    });

    it('limits to specified number of items', function(): void {
        $template = [
            'limited_items' => [
                'LIMIT' => 3,
                '*' => [
                    'id' => '{{ items.*.id }}',
                    'name' => '{{ items.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['limited_items'])->toHaveCount(3);
        expect($result['limited_items'][0]['id'])->toBe(1);
        expect($result['limited_items'][1]['id'])->toBe(2);
        expect($result['limited_items'][2]['id'])->toBe(3);
    });

    it('returns all items when limit is greater than array size', function(): void {
        $template = [
            'limited_items' => [
                'LIMIT' => 10,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['limited_items'])->toHaveCount(5);
    });

    it('returns empty array when limit is 0', function(): void {
        $template = [
            'limited_items' => [
                'LIMIT' => 0,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['limited_items'])->toHaveCount(0);
    });

    it('returns all items when limit is negative', function(): void {
        $template = [
            'limited_items' => [
                'LIMIT' => -1,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['limited_items'])->toHaveCount(5);
    });

    it('works with ORDER BY', function(): void {
        $template = [
            'sorted_limited_items' => [
                'ORDER BY' => [
                    '{{ items.*.id }}' => 'DESC',
                ],
                'LIMIT' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['sorted_limited_items'])->toHaveCount(2);
        expect($result['sorted_limited_items'][0]['id'])->toBe(5);
        expect($result['sorted_limited_items'][1]['id'])->toBe(4);
    });

    it('works with WHERE', function(): void {
        $sources = [
            'items' => [
                ['id' => 1, 'active' => true],
                ['id' => 2, 'active' => false],
                ['id' => 3, 'active' => true],
                ['id' => 4, 'active' => true],
                ['id' => 5, 'active' => false],
            ],
        ];

        $template = [
            'filtered_limited_items' => [
                'WHERE' => [
                    '{{ items.*.active }}' => true,
                ],
                'LIMIT' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources, true, true);

        expect($result['filtered_limited_items'])->toHaveCount(2);
        expect($result['filtered_limited_items'][0]['id'])->toBe(1);
        expect($result['filtered_limited_items'][1]['id'])->toBe(3);
    });

    it('recognizes lowercase limit', function(): void {
        $template = [
            'limited_items' => [
                'limit' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['limited_items'])->toHaveCount(2);
    });
});

