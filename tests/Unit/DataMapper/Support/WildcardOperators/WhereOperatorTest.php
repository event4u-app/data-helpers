<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Wildcard WHERE Clause', function(): void {
    beforeEach(function(): void {
        $this->sources = [
            'project' => [
                'number' => 'P-001',
            ],
            'positions' => [
                ['project_number' => 'P-001', 'pos_number' => '1.1', 'type' => 'gravel'],
                ['project_number' => 'P-001', 'pos_number' => '1.2', 'type' => 'sand'],
                ['project_number' => 'P-002', 'pos_number' => '2.1', 'type' => 'gravel'],
                ['project_number' => 'P-001', 'pos_number' => '1.3', 'type' => 'gravel'],
            ],
        ];
    });

    it('filters wildcard array with simple WHERE condition', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    '{{ positions.*.project_number }}' => '{{ project.number }}',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'type' => '{{ positions.*.type }}',
                ],
            ],
        ];

        $result = DataMapper::template($template)->sources($this->sources)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(3);
        expect($result['filtered_positions'][0]['number'])->toBe('1.1');
        expect($result['filtered_positions'][1]['number'])->toBe('1.2');
        expect($result['filtered_positions'][2]['number'])->toBe('1.3');
    });

    it('filters with AND condition (implicit)', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    '{{ positions.*.project_number }}' => '{{ project.number }}',
                    '{{ positions.*.type }}' => 'gravel',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(2);
        expect($result['filtered_positions'][0]['number'])->toBe('1.1');
        expect($result['filtered_positions'][1]['number'])->toBe('1.3');
    });

    it('filters with explicit AND condition', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'AND' => [
                        '{{ positions.*.project_number }}' => '{{ project.number }}',
                        '{{ positions.*.type }}' => 'gravel',
                    ],
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(2);
        expect($result['filtered_positions'][0]['number'])->toBe('1.1');
        expect($result['filtered_positions'][1]['number'])->toBe('1.3');
    });

    it('filters with OR condition', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'OR' => [
                        '{{ positions.*.type }}' => 'gravel',
                        '{{ positions.*.pos_number }}' => '1.2',
                    ],
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'type' => '{{ positions.*.type }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(4); // All except 2.1 which is sand
        expect($result['filtered_positions'][0]['number'])->toBe('1.1');
        expect($result['filtered_positions'][1]['number'])->toBe('1.2');
        expect($result['filtered_positions'][2]['number'])->toBe('2.1');
        expect($result['filtered_positions'][3]['number'])->toBe('1.3');
    });

    it('filters with nested AND/OR conditions', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'AND' => [
                        '{{ positions.*.project_number }}' => '{{ project.number }}',
                        'OR' => [
                            '{{ positions.*.type }}' => 'gravel',
                            '{{ positions.*.pos_number }}' => '1.2',
                        ],
                    ],
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                    'type' => '{{ positions.*.type }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(3);
        expect($result['filtered_positions'][0]['number'])->toBe('1.1');
        expect($result['filtered_positions'][1]['number'])->toBe('1.2');
        expect($result['filtered_positions'][2]['number'])->toBe('1.3');
    });

    it('handles case-insensitive AND/OR keywords', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'and' => [
                        '{{ positions.*.project_number }}' => '{{ project.number }}',
                        'or' => [
                            '{{ positions.*.type }}' => 'gravel',
                            '{{ positions.*.pos_number }}' => '1.2',
                        ],
                    ],
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(3);
    });

    it('handles deeply nested AND/OR conditions', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'OR' => [
                        [
                            'AND' => [
                                '{{ positions.*.project_number }}' => 'P-001',
                                '{{ positions.*.type }}' => 'gravel',
                            ],
                        ],
                        [
                            'AND' => [
                                '{{ positions.*.project_number }}' => 'P-002',
                                '{{ positions.*.type }}' => 'gravel',
                            ],
                        ],
                    ],
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(3);
        expect($result['filtered_positions'][0]['number'])->toBe('1.1');
        expect($result['filtered_positions'][1]['number'])->toBe('2.1');
        expect($result['filtered_positions'][2]['number'])->toBe('1.3');
    });

    it('returns empty array when no items match WHERE condition', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    '{{ positions.*.project_number }}' => 'P-999',
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toBeArray();
        expect($result['filtered_positions'])->toHaveCount(0);
    });

    it('works without WHERE clause (backward compatibility)', function(): void {
        $template = [
            'all_positions' => [
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['all_positions'])->toHaveCount(4);
    });

    it('filters with multiple OR groups', function(): void {
        $template = [
            'filtered_positions' => [
                'WHERE' => [
                    'OR' => [
                        ['{{ positions.*.type }}' => 'gravel'],
                        ['{{ positions.*.type }}' => 'sand'],
                    ],
                ],
                '*' => [
                    'number' => '{{ positions.*.pos_number }}',
                ],
            ],
        ];

        $result = DataMapper::source($this->sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_positions'])->toHaveCount(4);
    });

    it('handles complex real-world example from user', function(): void {
        $sources = [
            'ConstructionSite' => [
                'nr_lv' => 'CS-123',
                'Positions' => [
                    'Position' => [
                        ['project_number' => 'CS-123', 'pos_number' => '1.1', 'parent_id' => null, 'type_description' => 'Main'],
                        ['project_number' => 'CS-123', 'pos_number' => '1.1.1', 'parent_id' => '1.1', 'type_description' => 'Sub'],
                        ['project_number' => 'CS-999', 'pos_number' => '2.1', 'parent_id' => null, 'type_description' => 'Other'],
                        ['project_number' => 'CS-123', 'pos_number' => '1.2', 'parent_id' => null, 'type_description' => 'Main'],
                    ],
                ],
            ],
        ];

        $template = [
            'project' => [
                'number' => '{{ ConstructionSite.nr_lv }}',
            ],
            'positions' => [
                'where' => [
                    '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
                ],
                '*' => [
                    'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
                    'parent_id' => '{{ ConstructionSite.Positions.Position.*.parent_id }}',
                    'type_description' => '{{ ConstructionSite.Positions.Position.*.type_description }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)->template($template)->reindexWildcard(true)->map()->getTarget();

        expect($result['project']['number'])->toBe('CS-123');
        expect($result['positions'])->toHaveCount(3);
        expect($result['positions'][0]['number'])->toBe('1.1');
        expect($result['positions'][1]['number'])->toBe('1.1.1');
        expect($result['positions'][2]['number'])->toBe('1.2');
    });
});
