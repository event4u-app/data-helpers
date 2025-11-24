<?php

declare(strict_types=1);

namespace Tests\Unit\XMLs;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Support\FileLoader;

describe('XML with Multiple Root Elements', function(): void {
    it('loads XML file with multiple root elements', function(): void {
        $xmlFile = __DIR__ . '/../../Utils/XMLs/multi-root.xml';

        $result = DataMapper::sourceFile($xmlFile)
            ->template([
                'lv_id' => '{{ LVDATA.LV.ID_LV }}',
                'lv_number' => '{{ LVDATA.LV.NR_LV }}',
                'lv_status' => '{{ LVDATA.LV.LV_STATUS }}',
                'positions' => [
                    '*' => [
                        'position_id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                        'lv_id' => '{{ POSDATA.POS.*.ID_LV }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBeArray()
            ->and($result['lv_id'])->toBe('2076436701850')
            ->and($result['lv_number'])->toBe('K25749')
            ->and($result['lv_status'])->toBe('BZ')
            ->and($result['positions'])->toBeArray()
            ->and($result['positions'])->toHaveCount(3)
            ->and($result['positions'][0]['position_id'])->toBe('20756528901857')
            ->and($result['positions'][0]['lv_id'])->toBe('2076436701850')
            ->and($result['positions'][1]['position_id'])->toBe('2073642601852')
            ->and($result['positions'][1]['lv_id'])->toBe('2076436701850')
            ->and($result['positions'][2]['position_id'])->toBe('2075853401853')
            ->and($result['positions'][2]['lv_id'])->toBe('2076436701850');
    });

    it('loads XML file with multiple root elements as array', function(): void {
        $xmlFile = __DIR__ . '/../../Utils/XMLs/multi-root.xml';

        // Use FileLoader directly to check the loaded structure
        $result = FileLoader::loadAsArray($xmlFile);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('LVDATA')
            ->and($result)->toHaveKey('POSDATA')
            ->and($result['LVDATA'])->toHaveKey('LV')
            ->and($result['LVDATA']['LV'])->toHaveKey('ID_LV')
            ->and($result['LVDATA']['LV']['ID_LV'])->toBe('2076436701850')
            ->and($result['POSDATA'])->toHaveKey('POS')
            ->and($result['POSDATA']['POS'])->toBeArray()
            ->and($result['POSDATA']['POS'])->toHaveCount(3);
    });

    it('maps both root elements independently', function(): void {
        $xmlFile = __DIR__ . '/../../Utils/XMLs/multi-root.xml';

        // Map only LVDATA
        $lvResult = DataMapper::sourceFile($xmlFile)
            ->template([
                'id' => '{{ LVDATA.LV.ID_LV }}',
                'number' => '{{ LVDATA.LV.NR_LV }}',
                'status' => '{{ LVDATA.LV.LV_STATUS }}',
            ])
            ->map()
            ->getTarget();

        expect($lvResult)->toBeArray()
            ->and($lvResult['id'])->toBe('2076436701850')
            ->and($lvResult['number'])->toBe('K25749')
            ->and($lvResult['status'])->toBe('BZ');

        // Map only POSDATA
        $posResult = DataMapper::sourceFile($xmlFile)
            ->template([
                'positions' => [
                    '*' => [
                        'id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                        'lv_ref' => '{{ POSDATA.POS.*.ID_LV }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($posResult)->toBeArray()
            ->and($posResult['positions'])->toHaveCount(3)
            ->and($posResult['positions'][0]['id'])->toBe('20756528901857')
            ->and($posResult['positions'][1]['id'])->toBe('2073642601852')
            ->and($posResult['positions'][2]['id'])->toBe('2075853401853');
    });

    it('handles empty mapping with multiple root elements', function(): void {
        $xmlFile = __DIR__ . '/../../Utils/XMLs/multi-root.xml';

        $result = DataMapper::sourceFile($xmlFile)
            ->template([])
            ->map()
            ->getTarget();

        expect($result)->toBeArray()
            ->and($result)->toBeEmpty();
    });

    it('loads XML string with multiple root elements directly', function(): void {
        $xmlFile = __DIR__ . '/../../Utils/XMLs/multi-root.xml';
        $xmlString = file_get_contents($xmlFile);

        expect($xmlString)->toBeString();

        $result = DataMapper::source($xmlString)
            ->template([
                'lv_id' => '{{ LVDATA.LV.ID_LV }}',
                'lv_number' => '{{ LVDATA.LV.NR_LV }}',
                'lv_status' => '{{ LVDATA.LV.LV_STATUS }}',
                'positions' => [
                    '*' => [
                        'position_id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                        'lv_id' => '{{ POSDATA.POS.*.ID_LV }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBeArray()
            ->and($result['lv_id'])->toBe('2076436701850')
            ->and($result['lv_number'])->toBe('K25749')
            ->and($result['lv_status'])->toBe('BZ')
            ->and($result['positions'])->toBeArray()
            ->and($result['positions'])->toHaveCount(3)
            ->and($result['positions'][0]['position_id'])->toBe('20756528901857')
            ->and($result['positions'][0]['lv_id'])->toBe('2076436701850')
            ->and($result['positions'][1]['position_id'])->toBe('2073642601852')
            ->and($result['positions'][1]['lv_id'])->toBe('2076436701850')
            ->and($result['positions'][2]['position_id'])->toBe('2075853401853')
            ->and($result['positions'][2]['lv_id'])->toBe('2076436701850');
    });

    it('maps both root elements independently from XML string', function(): void {
        $xmlFile = __DIR__ . '/../../Utils/XMLs/multi-root.xml';
        $xmlString = file_get_contents($xmlFile);

        expect($xmlString)->toBeString();

        // Map only LVDATA
        $lvResult = DataMapper::source($xmlString)
            ->template([
                'id' => '{{ LVDATA.LV.ID_LV }}',
                'number' => '{{ LVDATA.LV.NR_LV }}',
                'status' => '{{ LVDATA.LV.LV_STATUS }}',
            ])
            ->map()
            ->getTarget();

        expect($lvResult)->toBeArray()
            ->and($lvResult['id'])->toBe('2076436701850')
            ->and($lvResult['number'])->toBe('K25749')
            ->and($lvResult['status'])->toBe('BZ');

        // Map only POSDATA
        $posResult = DataMapper::source($xmlString)
            ->template([
                'positions' => [
                    '*' => [
                        'id' => '{{ POSDATA.POS.*.ID_POSITION }}',
                        'lv_ref' => '{{ POSDATA.POS.*.ID_LV }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($posResult)->toBeArray()
            ->and($posResult['positions'])->toHaveCount(3)
            ->and($posResult['positions'][0]['id'])->toBe('20756528901857')
            ->and($posResult['positions'][1]['id'])->toBe('2073642601852')
            ->and($posResult['positions'][2]['id'])->toBe('2075853401853');
    });
});
