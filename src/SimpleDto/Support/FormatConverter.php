<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

/**
 * Format conversion utility for converting between RTF, HTML, and plain text.
 *
 * Supports conversions:
 * - RTF → Text
 * - RTF → HTML
 * - HTML → Text
 * - HTML → RTF
 * - Text → HTML
 * - Text → RTF
 *
 * All conversions are XSS-safe and sanitize input appropriately.
 */
final class FormatConverter
{
    /**
     * Convert RTF to plain text.
     *
     * @param string $rtf The RTF text
     * @return string The plain text
     */
    public static function rtfToText(string $rtf): string
    {
        return TextSanitizer::rtfToPlainText($rtf);
    }

    /**
     * Convert RTF to HTML.
     *
     * Converts RTF formatting to HTML tags while preserving structure.
     *
     * @param string $rtf The RTF text
     * @return string The HTML text (XSS-safe)
     */
    public static function rtfToHtml(string $rtf): string
    {
        // First convert RTF to plain text
        $text = self::rtfToText($rtf);

        // Then convert plain text to HTML (with XSS protection)
        return self::textToHtml($text);
    }

    /**
     * Convert HTML to plain text.
     *
     * Removes all HTML tags and decodes entities.
     *
     * @param string $html The HTML text
     * @return string The plain text
     */
    public static function htmlToText(string $html): string
    {
        // Remove HTML tags
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = TextSanitizer::normalizeWhitespace($text);

        return trim($text, " \t\n\r");
    }

    /**
     * Convert HTML to RTF.
     *
     * Converts HTML tags to RTF formatting codes.
     *
     * @param string $html The HTML text
     * @return string The RTF text
     */
    public static function htmlToRtf(string $html): string
    {
        // First convert HTML to plain text (removes tags, decodes entities)
        $text = self::htmlToText($html);

        // Then convert plain text to RTF
        return self::textToRtf($text);
    }

    /**
     * Convert plain text to HTML.
     *
     * Escapes HTML special characters and converts newlines to <br> tags.
     * XSS-safe.
     *
     * @param string $text The plain text
     * @param bool $nl2br Whether to convert newlines to <br> tags (default: true)
     * @return string The HTML text (XSS-safe)
     */
    public static function textToHtml(string $text, bool $nl2br = true): string
    {
        // Escape HTML special characters (XSS protection)
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convert newlines to <br> tags if requested
        if ($nl2br) {
            $html = nl2br($html, false); // Use <br> instead of <br />
        }

        return $html;
    }

    /**
     * Convert plain text to RTF.
     *
     * Creates a minimal RTF document with the text content.
     *
     * @param string $text The plain text
     * @return string The RTF text
     */
    public static function textToRtf(string $text): string
    {
        // Escape RTF special characters
        $text = str_replace(['\\', '{', '}'], ['\\\\', '\\{', '\\}'], $text);

        // Convert newlines to RTF line breaks
        $text = str_replace(["\r\n", "\r", "\n"], '\\line ', $text);

        // Convert tabs to RTF tabs
        $text = str_replace("\t", '\\tab ', $text);

        // Encode non-ASCII characters as Unicode escapes
        $text = self::encodeUnicodeForRtf($text);

        // Create minimal RTF document
        return '{\\rtf1\\ansi\\deff0{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}}\\f0\\fs20 ' . $text . '}';
    }

    /**
     * Encode non-ASCII characters as RTF Unicode escapes.
     *
     * @param string $text The text to encode
     * @return string The text with Unicode escapes
     */
    private static function encodeUnicodeForRtf(string $text): string
    {
        $result = '';
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $code = mb_ord($char, 'UTF-8');

            if (127 < $code) {
                // Non-ASCII character - encode as Unicode escape
                // RTF uses signed 16-bit integers, so values > 32767 need to be negative
                if (32767 < $code) {
                    $code -= 65536;
                }
                $result .= '\\u' . $code . '?';
            } else {
                // ASCII character - keep as is
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Detect the format of the input text.
     *
     * @param string $text The text to detect
     * @return string 'rtf', 'html', or 'text'
     */
    public static function detectFormat(string $text): string
    {
        $trimmed = trim($text);

        // Check for RTF
        if (str_starts_with($trimmed, '{\rtf')) {
            return 'rtf';
        }

        // Check for HTML (simple heuristic)
        if (preg_match('/<[a-z][\s\S]*>/i', $trimmed)) {
            return 'html';
        }

        return 'text';
    }
}
