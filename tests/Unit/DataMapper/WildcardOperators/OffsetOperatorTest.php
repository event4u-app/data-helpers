<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('OFFSET Operator', function(): void {
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

    it('skips specified number of items', function(): void {
        $template = [
            'offset_items' => [
                'OFFSET' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                    'name' => '{{ items.*.name }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['offset_items'])->toHaveCount(3);
        expect($result['offset_items'][0]['id'])->toBe(3);
        expect($result['offset_items'][1]['id'])->toBe(4);
        expect($result['offset_items'][2]['id'])->toBe(5);
    });

    it('returns empty array when offset is greater than array size', function(): void {
        $template = [
            'offset_items' => [
                'OFFSET' => 10,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['offset_items'])->toHaveCount(0);
    });

    it('returns all items when offset is 0', function(): void {
        $template = [
            'offset_items' => [
                'OFFSET' => 0,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['offset_items'])->toHaveCount(5);
    });

    it('returns all items when offset is negative', function(): void {
        $template = [
            'offset_items' => [
                'OFFSET' => -1,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['offset_items'])->toHaveCount(5);
    });

    it('works with LIMIT for pagination', function(): void {
        $template = [
            'paginated_items' => [
                'OFFSET' => 2,
                'LIMIT' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['paginated_items'])->toHaveCount(2);
        expect($result['paginated_items'][0]['id'])->toBe(3);
        expect($result['paginated_items'][1]['id'])->toBe(4);
    });

    it('works with ORDER BY', function(): void {
        $template = [
            'sorted_offset_items' => [
                'ORDER BY' => [
                    '{{ items.*.id }}' => 'DESC',
                ],
                'OFFSET' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['sorted_offset_items'])->toHaveCount(3);
        expect($result['sorted_offset_items'][0]['id'])->toBe(3);
        expect($result['sorted_offset_items'][1]['id'])->toBe(2);
        expect($result['sorted_offset_items'][2]['id'])->toBe(1);
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
            'filtered_offset_items' => [
                'WHERE' => [
                    '{{ items.*.active }}' => true,
                ],
                'OFFSET' => 1,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources, true, true);

        expect($result['filtered_offset_items'])->toHaveCount(2);
        expect($result['filtered_offset_items'][0]['id'])->toBe(3);
        expect($result['filtered_offset_items'][1]['id'])->toBe(4);
    });

    it('recognizes lowercase offset', function(): void {
        $template = [
            'offset_items' => [
                'offset' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['offset_items'])->toHaveCount(3);
    });
});

