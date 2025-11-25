<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('WHERE Operator with RECORD_STATE Default Value', function(): void {
    it('filters positions with RECORD_STATE using default value', function(): void {
        $sources = [
            'POSDATA' => [
                'POS' => [
                    [
                        'ID_POSITION' => '1',
                        'ID_LV' => 'LV-001',
                        'NR_POSITION' => '1.1',
                        'KURZTEXT_DISPLAY' => 'Position 1',
                        'RECORD_STATE' => '0',  // Should be included
                    ],
                    [
                        'ID_POSITION' => '2',
                        'ID_LV' => 'LV-001',
                        'NR_POSITION' => '1.2',
                        'KURZTEXT_DISPLAY' => 'Position 2',
                        'RECORD_STATE' => '1',  // Should be filtered out (archived)
                    ],
                    [
                        'ID_POSITION' => '3',
                        'ID_LV' => 'LV-001',
                        'NR_POSITION' => '1.3',
                        'KURZTEXT_DISPLAY' => 'Position 3',
                        // RECORD_STATE missing - should default to "0" and be included
                    ],
                    [
                        'ID_POSITION' => '4',
                        'ID_LV' => 'LV-001',
                        'NR_POSITION' => '1.4',
                        'KURZTEXT_DISPLAY' => 'Position 4',
                        'RECORD_STATE' => null,  // Should default to "0" and be included
                    ],
                    [
                        'ID_POSITION' => '5',
                        'ID_LV' => 'LV-001',
                        'NR_POSITION' => '1.5',
                        'KURZTEXT_DISPLAY' => 'Position 5',
                        'RECORD_STATE' => '1',  // Should be filtered out (archived)
                    ],
                ],
            ],
        ];

        $template = [
            'positions' => [
                // Filter archived positions (Section 3.1)
                // In dataflor, record_state = 1 stands for archive
                // Use default value "0" for missing RECORD_STATE fields
                'WHERE' => [
                    '{{ POSDATA.POS.*.RECORD_STATE ?? "0" }}' => ['!=', '1'],
                ],
                '*' => [
                    // External IDs and References (Section 3.1)
                    'id_position_ext' => '{{ POSDATA.POS.*.ID_POSITION }}',
                    'id_lv_ext' => '{{ POSDATA.POS.*.ID_LV }}',

                    // Position Number (Section 3.2)
                    'nr_position' => '{{ POSDATA.POS.*.NR_POSITION }}',

                    // Text Content (Section 3.2)
                    'kurztext' => '{{ POSDATA.POS.*.KURZTEXT_DISPLAY }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Should include positions 1, 3, and 4 (not 2 and 5 which are archived)
        expect($result['positions'])->toHaveCount(3)
            ->and($result['positions'][0]['id_position_ext'])->toBe('1')
            ->and($result['positions'][0]['nr_position'])->toBe('1.1')
            ->and($result['positions'][1]['id_position_ext'])->toBe('3')
            ->and($result['positions'][1]['nr_position'])->toBe('1.3')
            ->and($result['positions'][2]['id_position_ext'])->toBe('4')
            ->and($result['positions'][2]['nr_position'])->toBe('1.4');
    });

    it('handles RECORD_STATE with different data types', function(): void {
        $sources = [
            'POSDATA' => [
                'POS' => [
                    ['ID_POSITION' => '1', 'RECORD_STATE' => 0],  // Integer 0
                    ['ID_POSITION' => '2', 'RECORD_STATE' => 1],  // Integer 1
                    ['ID_POSITION' => '3', 'RECORD_STATE' => '0'],  // String "0"
                    ['ID_POSITION' => '4', 'RECORD_STATE' => '1'],  // String "1"
                    ['ID_POSITION' => '5'],  // Missing
                ],
            ],
        ];

        $template = [
            'positions' => [
                'WHERE' => [
                    '{{ POSDATA.POS.*.RECORD_STATE ?? "0" }}' => ['!=', '1'],
                ],
                '*' => [
                    'id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Should include positions 1, 3, and 5 (not 2 and 4)
        expect($result['positions'])->toHaveCount(3)
            ->and($result['positions'][0]['id'])->toBe('1')
            ->and($result['positions'][1]['id'])->toBe('3')
            ->and($result['positions'][2]['id'])->toBe('5');
    });

    it('handles empty string RECORD_STATE', function(): void {
        $sources = [
            'POSDATA' => [
                'POS' => [
                    ['ID_POSITION' => '1', 'RECORD_STATE' => ''],  // Empty string
                    ['ID_POSITION' => '2', 'RECORD_STATE' => '1'],
                    ['ID_POSITION' => '3', 'RECORD_STATE' => '0'],
                ],
            ],
        ];

        $template = [
            'positions' => [
                'WHERE' => [
                    '{{ POSDATA.POS.*.RECORD_STATE ?? "0" }}' => ['!=', '1'],
                ],
                '*' => [
                    'id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                ],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Empty string should not be replaced by default value
        // Should include positions 1 and 3 (not 2)
        expect($result['positions'])->toHaveCount(2)
            ->and($result['positions'][0]['id'])->toBe('1')
            ->and($result['positions'][1]['id'])->toBe('3');
    });
});
