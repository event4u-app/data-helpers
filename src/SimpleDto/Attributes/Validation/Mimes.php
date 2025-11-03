<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: File must have one of the given MIME types (by extension).
 *
 * This is a marker attribute for framework-specific validation (Laravel/Symfony).
 * It does NOT perform validation in SimpleDto itself - use a callback attribute for custom validation.
 *
 * Framework support:
 * - Laravel: Converts to 'mimes:ext1,ext2,...'
 * - Symfony: Uses Assert\File with mimeTypes constraint
 *
 * Examples:
 * ```php
 * class DocumentDto extends SimpleDto
 * {
 *     public function __construct(
 *         // PDF and Word documents
 *         #[Mimes(['pdf', 'doc', 'docx'])]
 *         public readonly mixed $document,
 *
 *         // Images
 *         #[Mimes(['jpg', 'png', 'gif'])]
 *         public readonly mixed $image,
 *
 *         // Spreadsheets
 *         #[Mimes(['csv', 'xlsx', 'xls'])]
 *         public readonly mixed $spreadsheet,
 *     ) {}
 * }
 * ```
 *
 * Note: This attribute is only useful when using SimpleDto with Laravel or Symfony validators.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Mimes
{
    /** @param array<string> $types Allowed file extensions */
    public function __construct(
        public readonly array $types,
    ) {}
}
