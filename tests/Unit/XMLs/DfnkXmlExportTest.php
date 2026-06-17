<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Template for the DFNKImport structure.
 * Static values (e.g. 'Webseite') are written directly; dynamic fields use {{ source.*.field }}.
 * The same template produces XML, JSON, or array output — only the final ->to*() call differs.
 *
 * @return array<string, mixed>
 */
function dfnkTemplate(): array
{
    return [
        'Lohn' => [
            '*' => [
                'Kennung'    => 'Webseite',
                'Datum'      => '{{ lohn.*.datum }}',
                'LVNr'       => '{{ lohn.*.lv_nr }}',
                'PosNr'      => '{{ lohn.*.pos_nr }}',
                'PersonalNr' => '{{ lohn.*.personal_nr }}',
                'Lohnart'    => '{{ lohn.*.lohnart }}',
                'GeraeteNr'  => '{{ lohn.*.geraete_nr }}',
                'Kostenart'  => '{{ lohn.*.kostenart }}',
                'Stunden'    => '{{ lohn.*.stunden }}',
                'Bemerkung'  => '{{ lohn.*.bemerkung }}',
            ],
        ],
        'Geraet' => [
            '*' => [
                'Kennung'   => 'Webseite',
                'Datum'     => '{{ geraet.*.datum }}',
                'LVNr'      => '{{ geraet.*.lv_nr }}',
                'PosNr'     => '{{ geraet.*.pos_nr }}',
                'GeraeteNr' => '{{ geraet.*.geraete_nr }}',
                'Stunden'   => '{{ geraet.*.stunden }}',
                'Bemerkung' => '{{ geraet.*.bemerkung }}',
            ],
        ],
    ];
}

/** @return array<string, mixed> */
function lohnEntry(
    string $lvNr = 'Gerät-01-0001',
    string $geraeteNr = 'L4',
    string $personalNr = '0',
    string $lohnart = '18',
    string $stunden = '1',
): array {
    return [
        'datum'       => '17.6.2026',
        'lv_nr'       => $lvNr,
        'pos_nr'      => '',
        'personal_nr' => $personalNr,
        'lohnart'     => $lohnart,
        'geraete_nr'  => $geraeteNr,
        'kostenart'   => 'Reparatur Lohn',
        'stunden'     => $stunden,
        'bemerkung'   => '',
    ];
}

/** @return array<string, mixed> */
function geraetEntry(string $lvNr = 'GL303', string $geraeteNr = 'HD001'): array
{
    return ['datum' => '17.6.2026', 'lv_nr' => $lvNr, 'pos_nr' => '', 'geraete_nr' => $geraeteNr, 'stunden' => '1', 'bemerkung' => ''];
}

// ──────────────────────────────────────────────────────────────────────────────
// DFNKImport export — XML, JSON and array from the same template
// ──────────────────────────────────────────────────────────────────────────────

describe('DFNK export via template', function(): void {
    it('maps one Lohn + one Geraet entry to the correct structure in all formats', function(): void {
        $result = DataMapper::source(['lohn' => [lohnEntry()], 'geraet' => [geraetEntry()]])
            ->template(dfnkTemplate())
            ->reindexWildcard(true)
            ->map();

        // Array — format-agnostic intermediate representation
        $array = $result->getTarget();
        expect($array['Lohn'])->toHaveCount(1)
            ->and($array['Lohn'][0]['Kennung'])->toBe('Webseite')     // static template value
            ->and($array['Lohn'][0]['LVNr'])->toBe('Gerät-01-0001')
            ->and($array['Lohn'][0]['GeraeteNr'])->toBe('L4')
            ->and($array['Geraet'])->toHaveCount(1)
            ->and($array['Geraet'][0]['LVNr'])->toBe('GL303');

        // XML — repeating sub-elements, no <item> wrappers
        $xml = $result->toXml('DFNKImport');
        expect($xml)->toContain('<DFNKImport>')
            ->and($xml)->toContain('<Lohn>')
            ->and($xml)->toContain('<Kennung>Webseite</Kennung>')
            ->and($xml)->toContain('<LVNr>Gerät-01-0001</LVNr>')
            ->and($xml)->toContain('<GeraeteNr>L4</GeraeteNr>')
            ->and($xml)->toContain('<Geraet>')
            ->and($xml)->toContain('<LVNr>GL303</LVNr>')
            ->and($xml)->not->toContain('<item>');

        // JSON — arrays of objects
        $json = json_decode($result->toJson(), true);
        expect($json['Lohn'])->toHaveCount(1)
            ->and($json['Lohn'][0]['Kennung'])->toBe('Webseite')
            ->and($json['Lohn'][0]['LVNr'])->toBe('Gerät-01-0001')
            ->and($json['Geraet'])->toHaveCount(1);
    });

    it('produces N sibling elements when source has N entries', function(): void {
        $result = DataMapper::source([
            'lohn' => [
                lohnEntry(lvNr: 'GL100', geraeteNr: 'L1'),
                lohnEntry(lvNr: 'GL101', geraeteNr: 'L2'),
                lohnEntry(lvNr: 'GL102', geraeteNr: 'L3'),
            ],
            'geraet' => [geraetEntry(geraeteNr: 'HD1'), geraetEntry(geraeteNr: 'HD2')],
        ])->template(dfnkTemplate())->reindexWildcard(true)->map();

        expect($result->getTarget()['Lohn'])->toHaveCount(3)
            ->and($result->getTarget()['Geraet'])->toHaveCount(2);

        $xml = $result->toXml('DFNKImport');
        expect(substr_count($xml, '<Lohn>'))->toBe(3)
            ->and(substr_count($xml, '<Geraet>'))->toBe(2)
            ->and($xml)->not->toContain('<item>');

        $json = json_decode($result->toJson(), true);
        expect($json['Lohn'])->toHaveCount(3)
            ->and($json['Geraet'])->toHaveCount(2);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// Edge cases
// ──────────────────────────────────────────────────────────────────────────────

describe('Edge cases for repeating-element exports', function(): void {
    it('empty source collection → empty array, self-closing XML tag, empty JSON array', function(): void {
        $result = DataMapper::source(['lohn' => [lohnEntry()], 'geraet' => []])
            ->template(dfnkTemplate())->reindexWildcard(true)->map();

        expect($result->getTarget()['Geraet'])->toBe([]);
        expect($result->toXml('DFNKImport'))->toMatch('/<Geraet(\/>|><\/Geraet>)/');
        expect(json_decode($result->toJson(), true)['Geraet'])->toBe([]);
    });

    it('missing source field → null in array, nil="true" in XML, null in JSON', function(): void {
        // pos_nr is intentionally absent from the source entry
        $source = ['datum' => '17.6.2026', 'lv_nr' => 'X', 'personal_nr' => '1',
            'lohnart' => '1', 'geraete_nr' => 'G', 'kostenart' => 'K', 'stunden' => '1', 'bemerkung' => ''];

        $result = DataMapper::source(['lohn' => [$source], 'geraet' => []])
            ->template(dfnkTemplate())->reindexWildcard(true)->map();

        expect($result->getTarget()['Lohn'][0]['PosNr'])->toBeNull();
        expect($result->toXml('DFNKImport'))->toContain('<PosNr nil="true"/>');
        expect(json_decode($result->toJson(), true)['Lohn'][0]['PosNr'])->toBeNull();
    });

    it('special characters are escaped in XML but preserved raw in JSON', function(): void {
        $result = DataMapper::source(['lohn' => [lohnEntry(lvNr: 'A&B <test>')], 'geraet' => []])
            ->template(dfnkTemplate())->reindexWildcard(true)->map();

        expect($result->toXml('DFNKImport'))->toContain('<LVNr>A&amp;B &lt;test&gt;</LVNr>');
        expect(json_decode($result->toJson(), true)['Lohn'][0]['LVNr'])->toBe('A&B <test>');
    });

    it('static template values appear on every item without being in the source', function(): void {
        $result = DataMapper::source([
            'lohn' => [lohnEntry(lvNr: 'A'), lohnEntry(lvNr: 'B')],
            'geraet' => [],
        ])->template(dfnkTemplate())->reindexWildcard(true)->map();

        $array = $result->getTarget();
        expect($array['Lohn'][0]['Kennung'])->toBe('Webseite')
            ->and($array['Lohn'][1]['Kennung'])->toBe('Webseite');

        $xml = $result->toXml('DFNKImport');
        expect(substr_count($xml, '<Kennung>Webseite</Kennung>'))->toBe(2);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// Generic repeating-element structure (not DFNK-specific)
// ──────────────────────────────────────────────────────────────────────────────

describe('Generic repeating-element export structures', function(): void {
    it('works for any root and sub-object type names', function(): void {
        $template = [
            'Artikel' => ['*' => [
                'Nr'          => '{{ artikel.*.nr }}',
                'Bezeichnung' => '{{ artikel.*.name }}',
                'Menge'       => '{{ artikel.*.menge }}',
            ]],
            'Dienstleistung' => ['*' => [
                'Nr'           => '{{ dienst.*.nr }}',
                'Beschreibung' => '{{ dienst.*.beschreibung }}',
            ]],
        ];

        $sources = [
            'artikel' => [
                ['nr' => '001', 'name' => 'Schraube M6', 'menge' => '10'],
                ['nr' => '002', 'name' => 'Mutter M6',   'menge' => '10'],
            ],
            'dienst' => [
                ['nr' => 'S01', 'beschreibung' => 'Montage'],
            ],
        ];

        $result = DataMapper::source($sources)
            ->template($template)->reindexWildcard(true)->map();

        $array = $result->getTarget();
        expect($array['Artikel'])->toHaveCount(2)
            ->and($array['Dienstleistung'])->toHaveCount(1);

        $xml = $result->toXml('Bestellung');
        expect(substr_count($xml, '<Artikel>'))->toBe(2)
            ->and(substr_count($xml, '<Dienstleistung>'))->toBe(1)
            ->and($xml)->not->toContain('<item>');

        $json = json_decode($result->toJson(), true);
        expect($json['Artikel'])->toHaveCount(2)
            ->and($json['Dienstleistung'])->toHaveCount(1)
            ->and($json['Artikel'][0]['Bezeichnung'])->toBe('Schraube M6');
    });
});
