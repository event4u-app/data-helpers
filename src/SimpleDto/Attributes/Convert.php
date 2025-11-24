<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;
use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;
use event4u\DataHelpers\SimpleDto\Support\FormatConverter;
use InvalidArgumentException;

/**
 * Convert between different text formats (RTF, HTML, Text).
 *
 * Supports conversions:
 * - RTF → Text: `#[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]`
 * - RTF → HTML: `#[Convert(ConvertFormat::RTF, ConvertFormat::HTML)]`
 * - HTML → Text: `#[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]`
 * - HTML → RTF: `#[Convert(ConvertFormat::HTML, ConvertFormat::RTF)]`
 * - Text → HTML: `#[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]`
 * - Text → RTF: `#[Convert(ConvertFormat::TEXT, ConvertFormat::RTF)]`
 *
 * All conversions are XSS-safe and sanitize input appropriately.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;
 *
 * class DocumentDto extends SimpleDto
 * {
 *     public function __construct(
 *         // Convert RTF to plain text
 *         #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
 *         public readonly string $description,
 *
 *         // Convert HTML to plain text
 *         #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
 *         public readonly string $content,
 *
 *         // Convert plain text to HTML (XSS-safe)
 *         #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
 *         public readonly string $htmlContent,
 *     ) {}
 * }
 * ```
 *
 * @see FormatConverter
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Convert implements TransformAttribute
{
    /**
     * @param ConvertFormat $from Source format
     * @param ConvertFormat $to Target format
     * @param bool $nl2br Whether to convert newlines to <br> tags when converting to HTML (default: true)
     * @throws InvalidArgumentException If source and target format are the same
     */
    public function __construct(
        public readonly ConvertFormat $from,
        public readonly ConvertFormat $to,
        public readonly bool $nl2br = true,
    ) {
        if ($this->from === $this->to) {
            throw new InvalidArgumentException(
                sprintf('Source and target format cannot be the same: "%s"', $this->from->value)
            );
        }
    }

    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $from = $this->from->value;
        $to = $this->to->value;

        return match (true) {
            'rtf' === $from && 'text' === $to => FormatConverter::rtfToText($value),
            'rtf' === $from && 'html' === $to => FormatConverter::rtfToHtml($value),
            'html' === $from && 'text' === $to => FormatConverter::htmlToText($value),
            'html' === $from && 'rtf' === $to => FormatConverter::htmlToRtf($value),
            'text' === $from && 'html' === $to => FormatConverter::textToHtml($value, $this->nl2br),
            'text' === $from && 'rtf' === $to => FormatConverter::textToRtf($value),
            default => $value,
        };
    }

    public function priority(): int
    {
        // Convert should run before Sanitize and Trim
        return 0;
    }
}
