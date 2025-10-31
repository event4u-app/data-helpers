<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: File must have one of the given MIME types (by actual MIME type).
 *
 * More strict than Mimes - checks actual MIME type, not just extension.
 *
 * This is a marker attribute for framework-specific validation (Laravel/Symfony).
 * It does NOT perform validation in SimpleDto itself - use a callback attribute for custom validation.
 *
 * Framework support:
 * - Laravel: Converts to 'mimetypes:type1,type2,...'
 * - Symfony: Uses Assert\File with mimeTypes constraint
 *
 * Examples:
 * ```php
 * class DocumentDto extends SimpleDto
 * {
 *     public function __construct(
 *         // PDF and Word documents
 *         #[MimeTypes(['application/pdf', 'application/msword'])]
 *         public readonly mixed $document,
 *
 *         // Images
 *         #[MimeTypes(['image/jpeg', 'image/png', 'image/gif'])]
 *         public readonly mixed $image,
 *
 *         // Spreadsheets
 *         #[MimeTypes(['text/csv', 'application/vnd.ms-excel'])]
 *         public readonly mixed $spreadsheet,
 *     ) {}
 * }
 * ```
 *
 * Note: This attribute is only useful when using SimpleDto with Laravel or Symfony validators.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MimeTypes
{
    /** @param array<string> $types Allowed MIME types */
    public function __construct(
        public readonly array $types,
    ) {}
}
