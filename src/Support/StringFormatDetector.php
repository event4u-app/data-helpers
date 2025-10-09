<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Utility class for detecting string formats (JSON, XML).
 */
final class StringFormatDetector
{
    /**
     * Check if a string is valid JSON.
     *
     * @param string $string The string to check
     * @return bool True if valid JSON, false otherwise
     */
    public static function isJson(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if a string is valid XML.
     *
     * @param string $string The string to check
     * @return bool True if valid XML, false otherwise
     */
    public static function isXml(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        // Check if string starts with XML declaration or root element
        $trimmed = trim($string);
        if (!str_starts_with($trimmed, '<?xml') && !str_starts_with($trimmed, '<')) {
            return false;
        }

        // Try to parse as XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($string);
        libxml_clear_errors();

        return false !== $xml;
    }

    /**
     * Detect the format of a string (json, xml, or null if unknown).
     *
     * @param string $string The string to analyze
     * @return string|null 'json', 'xml', or null if format cannot be determined
     */
    public static function detectFormat(string $string): ?string
    {
        return match (true) {
            self::isJson($string) => 'json',
            self::isXml($string) => 'xml',
            default => null,
        };
    }
}

