<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: Value must be a successfully uploaded file.
 *
 * This is a marker attribute for framework-specific validation (Laravel/Symfony).
 * It does NOT perform validation in LiteDto itself - use a callback attribute for custom validation.
 *
 * Framework support:
 * - Laravel: Converts to 'file' with optional 'max' and 'min' rules
 * - Symfony: Uses Assert\File constraint
 *
 * Examples:
 * ```php
 * class UploadDto extends LiteDto
 * {
 *     public function __construct(
 *         // Basic file validation
 *         #[File]
 *         public readonly mixed $document,
 *
 *         // With max size (in kilobytes)
 *         #[File(maxSize: 10240)]  // 10MB
 *         public readonly mixed $largeFile,
 *
 *         // With min and max size
 *         #[File(maxSize: 2048, minSize: 100)]
 *         public readonly mixed $avatar,
 *     ) {}
 * }
 * ```
 *
 * Note: This attribute is only useful when using LiteDto with Laravel or Symfony validators.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class File
{
    /**
     * @param int|null $maxSize Maximum file size in kilobytes
     * @param int|null $minSize Minimum file size in kilobytes
     */
    public function __construct(
        public readonly ?int $maxSize = null,
        public readonly ?int $minSize = null,
    ) {}
}
