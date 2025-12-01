<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

/**
 * Test WHERE operator with JSON nested format (like dataflor new-lvs.json)
 *
 * This test verifies that WHERE clauses work correctly with JSON data
 * that has nested arrays (LVs with positions).
 */
describe('WHERE Operator - JSON Nested Format (dataflor)', function(): void {
    beforeEach(function(): void {
        // Compact JSON data similar to dataflor new-lvs.json format
        // Array of LVs, each with nested positions array
        $this->jsonString = json_encode([
            [
                'ID_LV' => '123',
                'NR_LV' => 'TEST001',
                'BEZEICHNUNG' => 'Test Project 1',
                'positions' => [
                    [
                        'ID_POSITION' => '1',
                        'NR_POSITION' => '01',
                        'KURZTEXT_DISPLAY' => 'Position 1 - Normal',
                        'SGRAD' => '1',
                        'MENGE' => '10',
                    ],
                    [
                        'ID_POSITION' => '2',
                        'NR_POSITION' => '02',
                        'KURZTEXT_DISPLAY' => 'Position 2 - Difficult',
                        'SGRAD' => '1.5',
                        'MENGE' => '20',
                    ],
                    [
                        'ID_POSITION' => '3',
                        'NR_POSITION' => '03',
                        'KURZTEXT_DISPLAY' => 'Position 3 - Normal',
                        'SGRAD' => '1',
                        'MENGE' => '30',
                    ],
                ],
            ],
        ]);

        // Template to extract first LV and filter positions
        $this->template = [
            'lv_id' => '{{ 0.ID_LV }}',
            'lv_nr' => '{{ 0.NR_LV }}',
            'positions' => [
                // Filter positions with SGRAD != "1" (keep only normal difficulty)
                'WHERE' => [
                    '{{ 0.positions.*.SGRAD }}' => ['=', '1'],
                ],
                '*' => [
                    'id' => '{{ 0.positions.*.ID_POSITION }}',
                    'nr' => '{{ 0.positions.*.NR_POSITION }}',
                    'text' => '{{ 0.positions.*.KURZTEXT_DISPLAY }}',
                    'sgrad' => '{{ 0.positions.*.SGRAD }}',
                    'menge' => '{{ 0.positions.*.MENGE }}',
                ],
            ],
        ];
    });

    it('filters positions with WHERE clause on SGRAD field', function(): void {
        $result = DataMapper::source($this->jsonString)
            ->template($this->template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Should have LV data
        expect($result)->toHaveKey('lv_id');
        expect($result['lv_id'])->toBe('123');
        expect($result['lv_nr'])->toBe('TEST001');

        // Should have positions array
        expect($result)->toHaveKey('positions');
        expect($result['positions'])->toBeArray();

        // Should only have 2 positions (ID 1 and 3, filtered out ID 2 with SGRAD=1.5)
        expect($result['positions'])->toHaveCount(2);

        // Check first position (ID 1)
        expect($result['positions'][0]['id'])->toBe('1');
        expect($result['positions'][0]['sgrad'])->toBe('1');

        // Check second position (ID 3)
        expect($result['positions'][1]['id'])->toBe('3');
        expect($result['positions'][1]['sgrad'])->toBe('1');
    });

    it('filters positions with WHERE clause using ?? operator for missing fields', function(): void {
        // JSON data with some positions missing SGRAD field
        $jsonWithMissingFields = json_encode([
            [
                'ID_LV' => '456',
                'NR_LV' => 'TEST002',
                'positions' => [
                    [
                        'ID_POSITION' => '10',
                        'SGRAD' => '1',
                    ],
                    [
                        'ID_POSITION' => '20',
                        // SGRAD missing - should default to "1" via ?? operator
                    ],
                    [
                        'ID_POSITION' => '30',
                        'SGRAD' => '1.5',
                    ],
                ],
            ],
        ]);

        $template = [
            'positions' => [
                // Filter: keep only positions with SGRAD = "1" (use default "1" for missing)
                'WHERE' => [
                    '{{ 0.positions.*.SGRAD ?? "1" }}' => ['=', '1'],
                ],
                '*' => [
                    'id' => '{{ 0.positions.*.ID_POSITION }}',
                    'sgrad' => '{{ 0.positions.*.SGRAD ?? "1" }}',
                ],
            ],
        ];

        $result = DataMapper::source($jsonWithMissingFields)
            ->template($template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Should have 2 positions (ID 10 with SGRAD=1, ID 20 with default SGRAD=1)
        // ID 30 with SGRAD=1.5 should be filtered out
        expect($result['positions'])->toHaveCount(2);
        expect($result['positions'][0]['id'])->toBe('10');
        expect($result['positions'][0]['sgrad'])->toBe('1');
        expect($result['positions'][1]['id'])->toBe('20');
        expect($result['positions'][1]['sgrad'])->toBe('1'); // Default value
    });

    it('works with sourceFile() for JSON files', function(): void {
        // Create temporary JSON file
        // @phpstan-ignore-next-line disallowed.function (uniqid is fine for temp file names)
        $tempFile = sys_get_temp_dir() . '/test_json_nested_' . uniqid() . '.json';
        file_put_contents($tempFile, $this->jsonString);

        try {
            $result = DataMapper::sourceFile($tempFile)
                ->template($this->template)
                ->reindexWildcard(true)
                ->map()
                ->getTarget();

            // Should have same result as source()
            expect($result)->toHaveKey('positions');
            expect($result['positions'])->toHaveCount(2);
            expect($result['positions'][0]['id'])->toBe('1');
            expect($result['positions'][1]['id'])->toBe('3');
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    });

    it('produces identical results for source() and sourceFile()', function(): void {
        // Result from source()
        $resultFromSource = DataMapper::source($this->jsonString)
            ->template($this->template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Result from sourceFile()
        // @phpstan-ignore-next-line disallowed.function (uniqid is fine for temp file names)
        $tempFile = sys_get_temp_dir() . '/test_json_nested_' . uniqid() . '.json';
        file_put_contents($tempFile, $this->jsonString);

        try {
            $resultFromFile = DataMapper::sourceFile($tempFile)
                ->template($this->template)
                ->reindexWildcard(true)
                ->map()
                ->getTarget();

            // Results should be identical
            expect($resultFromSource)->toEqual($resultFromFile);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    });
});
