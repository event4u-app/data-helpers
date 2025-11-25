<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('WHERE Operator - XML Source Consistency (source() vs sourceFile())', function(): void {
    beforeEach(function(): void {
        // Invalid XML with multiple root elements (like dataflor export)
        $this->xmlString = <<<'XML'
<LVDATA>
    <LV>
        <ID_LV>1</ID_LV>
        <NR_LV>2024-001</NR_LV>
    </LV>
</LVDATA>
<POSDATA>
    <POS>
        <ID_POSITION>1</ID_POSITION>
        <ID_LV>1</ID_LV>
        <NR_POSITION>01</NR_POSITION>
        <KURZTEXT_DISPLAY>Position 1</KURZTEXT_DISPLAY>
        <RECORD_STATE>0</RECORD_STATE>
    </POS>
    <POS>
        <ID_POSITION>2</ID_POSITION>
        <ID_LV>1</ID_LV>
        <NR_POSITION>02</NR_POSITION>
        <KURZTEXT_DISPLAY>Position 2 (archived)</KURZTEXT_DISPLAY>
        <RECORD_STATE>1</RECORD_STATE>
    </POS>
    <POS>
        <ID_POSITION>3</ID_POSITION>
        <ID_LV>1</ID_LV>
        <NR_POSITION>03</NR_POSITION>
        <KURZTEXT_DISPLAY>Position 3 (no state)</KURZTEXT_DISPLAY>
    </POS>
</POSDATA>
XML;

        $this->template = [
            'lv_id' => '{{ LVDATA.LV.ID_LV }}',
            'lv_number' => '{{ LVDATA.LV.NR_LV }}',
            'positions' => [
                // Filter archived positions (RECORD_STATE = 1)
                // Use default value "0" for missing RECORD_STATE fields
                'WHERE' => [
                    '{{ POSDATA.POS.*.RECORD_STATE ?? "0" }}' => ['!=', '1'],
                ],
                '*' => [
                    'id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                    'lv_id' => '{{ POSDATA.POS.*.ID_LV }}',
                    'nr' => '{{ POSDATA.POS.*.NR_POSITION }}',
                    'text' => '{{ POSDATA.POS.*.KURZTEXT_DISPLAY }}',
                ],
            ],
        ];

        $this->expectedResult = [
            'lv_id' => '1',
            'lv_number' => '2024-001',
            'positions' => [
                [
                    'id' => '1',
                    'lv_id' => '1',
                    'nr' => '01',
                    'text' => 'Position 1',
                ],
                [
                    'id' => '3',
                    'lv_id' => '1',
                    'nr' => '03',
                    'text' => 'Position 3 (no state)',
                ],
            ],
        ];
    });

    it('filters positions with WHERE clause using sourceFile()', function(): void {
        // @phpstan-ignore-next-line disallowed.function (uniqid is fine for temp file names)
        $tempFile = sys_get_temp_dir() . '/test_where_xml_' . uniqid() . '.xml';
        file_put_contents($tempFile, $this->xmlString);

        $result = DataMapper::sourceFile($tempFile)
            ->template($this->template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        unlink($tempFile);

        // Should include positions 1 and 3 (not 2 which has RECORD_STATE=1)
        expect($result)->toBe($this->expectedResult);
    });

    it('filters positions with WHERE clause using source() with XML string', function(): void {
        $result = DataMapper::source($this->xmlString)
            ->template($this->template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Should produce the same result as sourceFile()
        expect($result)->toBe($this->expectedResult);
    });

    it('produces identical results for source() and sourceFile()', function(): void {
        // Result from sourceFile()
        // @phpstan-ignore-next-line disallowed.function (uniqid is fine for temp file names)
        $tempFile = sys_get_temp_dir() . '/test_where_xml_' . uniqid() . '.xml';
        file_put_contents($tempFile, $this->xmlString);

        $resultFromFile = DataMapper::sourceFile($tempFile)
            ->template($this->template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        unlink($tempFile);

        // Result from source() with XML string
        $resultFromString = DataMapper::source($this->xmlString)
            ->template($this->template)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        // Both results should be identical
        expect($resultFromString)->toBe($resultFromFile);
    });
});
