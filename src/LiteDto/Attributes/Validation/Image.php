<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;

/**
 * Validation attribute: Value must be an image file.
 *
 * This is a marker attribute for framework-specific validation (Laravel/Symfony).
 * It does NOT perform validation in LiteDto itself - use a callback attribute for custom validation.
 *
 * Framework support:
 * - Laravel: Converts to 'image' with optional 'mimes', 'max', and 'dimensions' rules
 * - Symfony: Uses Assert\Image constraint
 *
 * Examples:
 * ```php
 * class ProfileDto extends LiteDto
 * {
 *     public function __construct(
 *         // Basic image validation
 *         #[Image]
 *         public readonly mixed $avatar,
 *
 *         // With MIME types and max size
 *         #[Image(mimes: ['jpg', 'png'], maxSize: 2048)]
 *         public readonly mixed $photo,
 *
 *         // With dimensions
 *         #[Image(minWidth: 800, maxWidth: 1920, minHeight: 600, maxHeight: 1080)]
 *         public readonly mixed $banner,
 *
 *         // Complete example
 *         #[Image(
 *             mimes: ['jpg', 'png', 'webp'],
 *             maxSize: 5120,
 *             minWidth: 400,
 *             maxWidth: 2000,
 *             minHeight: 300,
 *             maxHeight: 1500
 *         )]
 *         public readonly mixed $headerImage,
 *     ) {}
 * }
 * ```
 *
 * Note: This attribute is only useful when using LiteDto with Laravel or Symfony validators.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Image
{
    /**
     * @param array<string>|null $mimes Allowed MIME types (jpg, png, gif, etc.)
     * @param int|null $maxSize Maximum file size in kilobytes
     * @param int|null $minWidth Minimum image width in pixels
     * @param int|null $maxWidth Maximum image width in pixels
     * @param int|null $minHeight Minimum image height in pixels
     * @param int|null $maxHeight Maximum image height in pixels
     */
    public function __construct(
        public readonly ?array $mimes = null,
        public readonly ?int $maxSize = null,
        public readonly ?int $minWidth = null,
        public readonly ?int $maxWidth = null,
        public readonly ?int $minHeight = null,
        public readonly ?int $maxHeight = null,
    ) {}
}
