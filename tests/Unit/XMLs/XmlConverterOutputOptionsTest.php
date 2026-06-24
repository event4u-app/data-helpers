<?php

declare(strict_types=1);

use event4u\DataHelpers\Converters\XmlConverter;
use event4u\DataHelpers\DataMapper;

describe('XmlConverter output options', function(): void {
    it('keeps the XML declaration by default', function(): void {
        $xml = (new XmlConverter('root'))->fromArray(['a' => '1']);

        expect($xml)->toContain('<?xml');
    });

    it('omits the XML declaration when includeDeclaration is false', function(): void {
        $xml = (new XmlConverter('root', includeDeclaration: false))->fromArray(['a' => '1']);

        expect($xml)->not->toContain('<?xml')
            ->and($xml)->toContain('<root>')
            ->and($xml)->toContain('<a>1</a>');
    });

    it('emits nil="true" for null leaves by default', function(): void {
        $xml = (new XmlConverter('root'))->fromArray(['b' => null]);

        expect($xml)->toContain('<b nil="true"/>');
    });

    it('omits null leaves entirely when skipNullValues is true', function(): void {
        $xml = (new XmlConverter('root', skipNullValues: true))->fromArray(['a' => '1', 'b' => null]);

        expect($xml)->toContain('<a>1</a>')
            ->and($xml)->not->toContain('<b')
            ->and($xml)->not->toContain('nil');
    });

    it('self-closes empty elements by default', function(): void {
        $xml = (new XmlConverter('root'))->fromArray(['a' => '']);

        expect($xml)->toContain('<a/>');
    });

    it('expands empty elements when expandEmptyElements is true', function(): void {
        $xml = (new XmlConverter('root', expandEmptyElements: true))->fromArray(['a' => '']);

        expect($xml)->toContain('<a></a>')
            ->and($xml)->not->toContain('<a/>');
    });

    it('combines declaration-off, skip-null and expand-empty for a DFNK-style export', function(): void {
        $xml = (new XmlConverter(
            'DFNKImport',
            includeDeclaration: false,
            skipNullValues: true,
            expandEmptyElements: true,
        ))->fromArray([
            'Lohn' => [
                ['LVNr' => 'X', 'IstTaglohn' => null, 'PosNr' => ''],
            ],
        ]);

        expect($xml)->not->toContain('<?xml')
            ->and($xml)->not->toContain('IstTaglohn')
            ->and($xml)->toContain('<LVNr>X</LVNr>')
            ->and($xml)->toContain('<PosNr></PosNr>');
    });

    it('renders an empty array as an empty element by default', function(): void {
        $xml = (new XmlConverter('DFNKImport'))->fromArray(['Lohn' => [], 'Geraet' => [['LVNr' => 'X']]]);

        expect($xml)->toMatch('/<Lohn(\/>|><\/Lohn>)/')
            ->and($xml)->toContain('<Geraet>');
    });

    it('omits empty arrays entirely when skipEmptyArrays is true', function(): void {
        $xml = (new XmlConverter('DFNKImport', skipEmptyArrays: true))
            ->fromArray(['Lohn' => [], 'Geraet' => [['LVNr' => 'X']]]);

        expect($xml)->not->toContain('<Lohn>')
            ->and($xml)->not->toContain('<Lohn/>')
            ->and($xml)->toContain('<Geraet>')
            ->and($xml)->toContain('<LVNr>X</LVNr>');
    });

    it('passes the options through DataMapperResult::toXml()', function(): void {
        $xml = DataMapper::source(['rows' => [['LVNr' => 'X', 'IstTaglohn' => null]]])
            ->template(['Lohn' => ['*' => ['LVNr' => '{{ rows.*.LVNr }}', 'IstTaglohn' => '{{ rows.*.IstTaglohn }}']]])
            ->reindexWildcard(true)
            ->map()
            ->toXml('DFNKImport', includeDeclaration: false, skipNullValues: true);

        expect($xml)->not->toContain('<?xml')
            ->and($xml)->not->toContain('IstTaglohn')
            ->and($xml)->toContain('<LVNr>X</LVNr>');
    });
});
