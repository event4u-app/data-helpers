<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\StringFormatDetector;

describe('StringFormatDetector', function(): void {
    describe('isJson()', function(): void {
        it('detects valid JSON object', function(): void {
            expect(StringFormatDetector::isJson('{"key": "value"}'))->toBeTrue();
        });

        it('detects valid JSON array', function(): void {
            expect(StringFormatDetector::isJson('["item1", "item2"]'))->toBeTrue();
        });

        it('detects valid JSON string', function(): void {
            expect(StringFormatDetector::isJson('"simple string"'))->toBeTrue();
        });

        it('detects valid JSON number', function(): void {
            expect(StringFormatDetector::isJson('42'))->toBeTrue();
            expect(StringFormatDetector::isJson('3.14'))->toBeTrue();
        });

        it('detects valid JSON boolean', function(): void {
            expect(StringFormatDetector::isJson('true'))->toBeTrue();
            expect(StringFormatDetector::isJson('false'))->toBeTrue();
        });

        it('detects valid JSON null', function(): void {
            expect(StringFormatDetector::isJson('null'))->toBeTrue();
        });

        it('detects nested JSON', function(): void {
            $json = '{"user": {"name": "John", "age": 30, "tags": ["admin", "user"]}}';
            expect(StringFormatDetector::isJson($json))->toBeTrue();
        });

        it('rejects invalid JSON', function(): void {
            expect(StringFormatDetector::isJson('{invalid}'))->toBeFalse();
            expect(StringFormatDetector::isJson('{"key": value}'))->toBeFalse();
            expect(StringFormatDetector::isJson('not json'))->toBeFalse();
        });

        it('rejects empty string', function(): void {
            expect(StringFormatDetector::isJson(''))->toBeFalse();
        });

        it('handles JSON with whitespace', function(): void {
            expect(StringFormatDetector::isJson('  {"key": "value"}  '))->toBeTrue();
        });
    });

    describe('isXml()', function(): void {
        it('detects valid XML with declaration', function(): void {
            $xml = '<?xml version="1.0"?><root><item>value</item></root>';
            expect(StringFormatDetector::isXml($xml))->toBeTrue();
        });

        it('detects valid XML without declaration', function(): void {
            expect(StringFormatDetector::isXml('<root><item>value</item></root>'))->toBeTrue();
        });

        it('detects simple XML element', function(): void {
            expect(StringFormatDetector::isXml('<root />'))->toBeTrue();
            expect(StringFormatDetector::isXml('<root></root>'))->toBeTrue();
        });

        it('detects XML with attributes', function(): void {
            expect(StringFormatDetector::isXml('<root attr="value"><item id="1">text</item></root>'))->toBeTrue();
        });

        it('detects nested XML', function(): void {
            $xml = '<root><level1><level2><level3>value</level3></level2></level1></root>';
            expect(StringFormatDetector::isXml($xml))->toBeTrue();
        });

        it('rejects invalid XML', function(): void {
            expect(StringFormatDetector::isXml('<root><unclosed>'))->toBeFalse();
            expect(StringFormatDetector::isXml('not xml'))->toBeFalse();
        });

        it('rejects empty string', function(): void {
            expect(StringFormatDetector::isXml(''))->toBeFalse();
        });

        it('rejects string not starting with < or <?xml', function(): void {
            expect(StringFormatDetector::isXml('text before <root></root>'))->toBeFalse();
        });

        it('handles XML with whitespace', function(): void {
            expect(StringFormatDetector::isXml('  <root></root>  '))->toBeTrue();
        });

        it('handles XML with CDATA', function(): void {
            $xml = '<root><![CDATA[Some text with <special> characters]]></root>';
            expect(StringFormatDetector::isXml($xml))->toBeTrue();
        });
    });

    describe('detectFormat()', function(): void {
        it('detects JSON format', function(): void {
            expect(StringFormatDetector::detectFormat('{"key": "value"}'))->toBe('json');
            expect(StringFormatDetector::detectFormat('["item"]'))->toBe('json');
        });

        it('detects XML format', function(): void {
            expect(StringFormatDetector::detectFormat('<root></root>'))->toBe('xml');
            expect(StringFormatDetector::detectFormat('<?xml version="1.0"?><root />'))->toBe('xml');
        });

        it('returns null for unknown format', function(): void {
            expect(StringFormatDetector::detectFormat('plain text'))->toBeNull();
            expect(StringFormatDetector::detectFormat(''))->toBeNull();
        });

        it('prioritizes JSON over XML for ambiguous strings', function(): void {
            // JSON is checked first, so valid JSON wins
            expect(StringFormatDetector::detectFormat('42'))->toBe('json');
            expect(StringFormatDetector::detectFormat('true'))->toBe('json');
        });

        it('handles complex JSON', function(): void {
            $json = '{"users": [{"name": "John", "age": 30}, {"name": "Jane", "age": 25}]}';
            expect(StringFormatDetector::detectFormat($json))->toBe('json');
        });

        it('handles complex XML', function(): void {
            $xml = '<users><user><name>John</name><age>30</age></user></users>';
            expect(StringFormatDetector::detectFormat($xml))->toBe('xml');
        });
    });
});
