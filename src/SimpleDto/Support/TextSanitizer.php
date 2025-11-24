<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

/**
 * Text sanitization utility for cleaning and normalizing text.
 *
 * Handles:
 * - RTF (Rich Text Format) to plain text conversion
 * - HTML tag removal/conversion
 * - HTML entity decoding
 * - Whitespace normalization
 * - Control character removal
 */
final class TextSanitizer
{
    /**
     * Sanitize text by applying various cleaning operations.
     *
     * @param string $text The text to sanitize
     * @param bool $stripHtml Whether to strip HTML tags
     * @param bool $decodeHtmlEntities Whether to decode HTML entities
     * @param bool $normalizeWhitespace Whether to normalize whitespace
     * @param bool $removeControlChars Whether to remove control characters
     * @return string The sanitized text
     */
    public static function sanitize(
        string $text,
        bool $stripHtml = true,
        bool $decodeHtmlEntities = true,
        bool $normalizeWhitespace = true,
        bool $removeControlChars = true
    ): string {
        // Step 1: Convert RTF to plain text if detected
        if (self::isRtf($text)) {
            $text = self::rtfToPlainText($text);
        }

        // Step 2: Handle HTML
        if ($stripHtml) {
            $text = strip_tags($text);
        }

        if ($decodeHtmlEntities) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Step 3: Remove control characters (except newlines and tabs)
        if ($removeControlChars) {
            $text = self::removeControlCharacters($text);
        }

        // Step 4: Normalize whitespace
        if ($normalizeWhitespace) {
            $text = self::normalizeWhitespace($text);
            // Trim only spaces, tabs, and newlines (not other control characters)
            $text = trim($text, " \t\n\r");
        }

        return $text;
    }

    /**
     * Check if text is RTF format.
     *
     * @param string $text The text to check
     * @return bool True if text appears to be RTF
     */
    public static function isRtf(string $text): bool
    {
        return str_starts_with(trim($text), '{\rtf');
    }

    /**
     * Convert RTF text to plain text.
     *
     * This is a simplified RTF parser that handles common RTF constructs:
     * - Control words (\rtf1, \ansi, \par, \line, etc.)
     * - Control symbols (\', \{, \}, \\)
     * - Font tables and other groups
     * - Unicode escapes
     *
     * @param string $rtf The RTF text
     * @return string The plain text
     */
    public static function rtfToPlainText(string $rtf): string
    {
        // Remove RTF header and font table
        $text = preg_replace('/\{\\\\rtf[0-9].*?\{\\\\fonttbl.*?\}\s*/s', '', $rtf) ?? $rtf;

        // Remove color table
        $text = preg_replace('/\{\\\\colortbl;.*?\}/s', '', $text) ?? $text;

        // Remove stylesheet
        $text = preg_replace('/\{\\\\stylesheet.*?\}/s', '', $text) ?? $text;

        // Remove info group
        $text = preg_replace('/\{\\\\info.*?\}/s', '', $text) ?? $text;

        // Convert RTF line breaks
        $text = str_replace(['\line', '\par'], "\n", $text);

        // Convert RTF tabs
        $text = str_replace('\tab', "\t", $text);

        // Handle Unicode escapes (\u1234?)
        $text = preg_replace_callback(
            '/\\\\u(-?[0-9]+)\??/',
            static function (array $matches): string {
                $code = (int)$matches[1];
                if ($code < 0) {
                    $code = 65536 + $code;
                }
                return mb_chr($code, 'UTF-8') ?: '';
            },
            $text
        ) ?? $text;

        // Handle hex escapes (\'e4 = ä)
        // RTF uses Windows-1252 encoding for hex escapes
        $text = preg_replace_callback(
            "/\\\\'([0-9a-fA-F]{2})/",
            static function (array $matches): string {
                $byte = chr((int)hexdec($matches[1]));
                // Convert from Windows-1252 to UTF-8
                return mb_convert_encoding($byte, 'UTF-8', 'Windows-1252');
            },
            $text
        ) ?? $text;

        // Remove control words with parameters (\fs20, \lang1031, etc.)
        $text = preg_replace('/\\\\[a-z]+[0-9]+\s?/', '', $text) ?? $text;

        // Remove control words without parameters (\ansi, \deff, etc.)
        $text = preg_replace('/\\\\[a-z]+\s?/', '', $text) ?? $text;

        // Remove control symbols
        $text = str_replace(['\{', '\}', '\\\\'], ['{', '}', '\\'], $text);

        // Remove remaining braces and cleanup
        $text = str_replace(['{', '}'], '', $text);

        return $text;
    }

    /**
     * Remove control characters except newlines and tabs.
     *
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    public static function removeControlCharacters(string $text): string
    {
        // Remove all control characters except \n (10), \r (13), and \t (9)
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
    }

    /**
     * Normalize whitespace in text.
     *
     * - Converts multiple spaces/tabs to single space
     * - Normalizes line breaks (CRLF, CR, LF -> LF)
     * - Removes trailing whitespace from lines
     * - Removes excessive blank lines (max 2 consecutive)
     *
     * @param string $text The text to normalize
     * @return string The normalized text
     */
    public static function normalizeWhitespace(string $text): string
    {
        // Normalize line endings (CRLF, CR -> LF)
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove trailing whitespace from each line
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;

        // Replace multiple spaces/tabs with single space (but preserve newlines)
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        // Remove excessive blank lines (more than 2 consecutive newlines)
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return $text;
    }
}

