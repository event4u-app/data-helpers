<?php

declare(strict_types=1);

use event4u\DataHelpers\Converters\CsvConverter;
use event4u\DataHelpers\DataMapper;

describe('CsvConverter output options', function(): void {
    it('omits the final newline by default', function(): void {
        $csv = (new CsvConverter(includeHeaders: false, delimiter: ';', lineEnding: "\n"))
            ->fromArray([['a' => '1'], ['a' => '2']]);

        expect($csv)->toBe("1\n2");
    });

    it('uses the configured line ending and a final newline', function(): void {
        $csv = (new CsvConverter(includeHeaders: false, delimiter: ';', lineEnding: "\r\n", finalNewline: true))
            ->fromArray([['a' => '1', 'b' => '2'], ['a' => '3', 'b' => '4']]);

        expect($csv)->toBe("1;2\r\n3;4\r\n");
    });

    it('appends a trailing delimiter to every line when enabled', function(): void {
        $csv = (new CsvConverter(includeHeaders: false, delimiter: ';', trailingDelimiter: true, lineEnding: "\n"))
            ->fromArray([['a' => '1', 'b' => '2']]);

        expect($csv)->toBe('1;2;');
    });

    it('encloses fields per RFC by default', function(): void {
        $csv = (new CsvConverter(includeHeaders: false, delimiter: ';', lineEnding: "\n"))
            ->fromArray([['a' => 'x;y']]);

        expect($csv)->toBe('"x;y"');
    });

    it('strips the delimiter and line breaks from fields under quoting none', function(): void {
        $csv = (new CsvConverter(includeHeaders: false, delimiter: ';', quoting: 'none', lineEnding: "\n"))
            ->fromArray([['a' => 'x;y', 'b' => "line1\nline2"]]);

        expect($csv)->toBe('xy;line1 line2');
    });

    it('combines none-quoting, trailing delimiter, CRLF and final newline (legacy raw CSV)', function(): void {
        $csv = (new CsvConverter(
            includeHeaders: false,
            delimiter: ';',
            quoting: 'none',
            trailingDelimiter: true,
            lineEnding: "\r\n",
            finalNewline: true,
        ))->fromArray([['a' => '1', 'b' => '2'], ['a' => '3', 'b' => '4']]);

        expect($csv)->toBe("1;2;\r\n3;4;\r\n");
    });

    it('passes the options through DataMapperResult::toCsv()', function(): void {
        $csv = DataMapper::source(['rows' => [['a' => '1', 'b' => '2']]])
            ->template(['*' => ['a' => '{{ rows.*.a }}', 'b' => '{{ rows.*.b }}']])
            ->reindexWildcard(true)
            ->map()
            ->toCsv(
                includeHeaders: false,
                delimiter: ';',
                trailingDelimiter: true,
                lineEnding: "\r\n",
                finalNewline: true
            );

        expect($csv)->toBe("1;2;\r\n");
    });
});
