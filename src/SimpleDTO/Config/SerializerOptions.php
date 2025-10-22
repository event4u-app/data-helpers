<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Config;

/**
 * Configuration options for serializers.
 *
 * This class provides type-safe configuration for all serializers.
 * Different serializers use different options - only relevant options are used.
 *
 * Note: This is a simple configuration class, NOT a SimpleDTO.
 * It doesn't need DTO features like serialization, validation, etc.
 *
 * @example
 * ```php
 * // JSON with pretty print
 * $options = SerializerOptions::json(prettyPrint: true);
 *
 * // XML with custom root and encoding
 * $options = SerializerOptions::xml(rootElement: 'user', encoding: 'UTF-8');
 *
 * // YAML with custom indent
 * $options = SerializerOptions::yaml(indent: 4);
 *
 * // CSV with custom delimiter
 * $options = SerializerOptions::csv(delimiter: ';', includeHeaders: true);
 * ```
 */
final readonly class SerializerOptions
{
    /**
     * Create serializer options.
     *
     * @param bool $prettyPrint Whether to pretty-print JSON output
     * @param string $rootElement Root element name for XML
     * @param string $xmlVersion XML version
     * @param string $encoding Character encoding (XML, CSV)
     * @param int $indent Indentation level for YAML
     * @param bool $includeHeaders Whether to include headers in CSV
     * @param string $delimiter CSV delimiter character
     * @param string $enclosure CSV enclosure character
     * @param string $escape CSV escape character
     */
    public function __construct(
        public bool $prettyPrint = false,
        public string $rootElement = 'root',
        public string $xmlVersion = '1.0',
        public string $encoding = 'UTF-8',
        public int $indent = 2,
        public bool $includeHeaders = true,
        public string $delimiter = ',',
        public string $enclosure = '"',
        public string $escape = '\\',
    ) {
    }

    /** Create options with default values. */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create options for JSON serialization.
     *
     * @param bool $prettyPrint Whether to pretty-print the output
     */
    public static function json(bool $prettyPrint = false): self
    {
        return new self(prettyPrint: $prettyPrint);
    }

    /**
     * Create options for XML serialization.
     *
     * @param string $rootElement Root element name
     * @param string $xmlVersion XML version
     * @param string $encoding Character encoding
     */
    public static function xml(
        string $rootElement = 'root',
        string $xmlVersion = '1.0',
        string $encoding = 'UTF-8'
    ): self {
        return new self(
            rootElement: $rootElement,
            xmlVersion: $xmlVersion,
            encoding: $encoding,
        );
    }

    /**
     * Create options for YAML serialization.
     *
     * @param int $indent Indentation level (number of spaces)
     */
    public static function yaml(int $indent = 2): self
    {
        return new self(indent: $indent);
    }

    /**
     * Create options for CSV serialization.
     *
     * @param bool $includeHeaders Whether to include headers
     * @param string $delimiter Delimiter character
     * @param string $enclosure Enclosure character
     * @param string $escape Escape character
     */
    public static function csv(
        bool $includeHeaders = true,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): self {
        return new self(
            includeHeaders: $includeHeaders,
            delimiter: $delimiter,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    /** Create options for pretty-printed JSON. */
    public static function prettyJson(): self
    {
        return new self(prettyPrint: true);
    }

    /**
     * Create options for TSV (Tab-Separated Values).
     *
     * @param bool $includeHeaders Whether to include headers
     */
    public static function tsv(bool $includeHeaders = true): self
    {
        return new self(
            includeHeaders: $includeHeaders,
            delimiter: "\t",
        );
    }

    /**
     * Create options for semicolon-separated CSV (common in Europe).
     *
     * @param bool $includeHeaders Whether to include headers
     */
    public static function csvSemicolon(bool $includeHeaders = true): self
    {
        return new self(
            includeHeaders: $includeHeaders,
            delimiter: ';',
        );
    }
}
