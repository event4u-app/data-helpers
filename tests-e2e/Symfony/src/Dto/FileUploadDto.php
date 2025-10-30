<?php

declare(strict_types=1);

namespace App\Dto;

use event4u\DataHelpers\LiteDto\Attributes\Validation\FileCallback;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\LiteDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * DTO for testing FileCallback validation with Symfony.
 */
class FileUploadDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[FileCallback([self::class, 'validateFileSize'])]
        public readonly UploadedFile|array|null $document = null,

        #[FileCallback([self::class, 'validateImageMimeType'])]
        public readonly UploadedFile|array|null $avatar = null,

        #[FileCallback([self::class, 'validateImageDimensions'])]
        public readonly UploadedFile|array|null $banner = null,

        #[FileCallback([self::class, 'validatePdfSignature'])]
        public readonly UploadedFile|array|null $contract = null,
    ) {}

    /**
     * Validate file size (max 2MB).
     */
    public static function validateFileSize(mixed $value, string $propertyName): bool
    {
        if ($value instanceof UploadedFile) {
            // Symfony UploadedFile: size in bytes
            return $value->getSize() <= 2 * 1024 * 1024; // 2MB
        }

        if (is_array($value) && isset($value['size'])) {
            // Array format (PHP $_FILES)
            return $value['size'] <= 2 * 1024 * 1024; // 2MB
        }

        return false;
    }

    /**
     * Validate image MIME type (PNG, JPEG, GIF, WebP).
     */
    public static function validateImageMimeType(mixed $value, string $propertyName): bool
    {
        if ($value instanceof UploadedFile) {
            $mimeType = $value->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            return in_array($mimeType, $allowedMimes, true);
        }

        if (is_array($value) && isset($value['tmp_name'])) {
            if (!file_exists($value['tmp_name'])) {
                return false;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $value['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            return in_array($mimeType, $allowedMimes, true);
        }

        return false;
    }

    /**
     * Validate image dimensions (min 100x100, max 2000x2000, must be square).
     */
    public static function validateImageDimensions(mixed $value, string $propertyName): bool
    {
        $path = null;

        if ($value instanceof UploadedFile) {
            $path = $value->getRealPath();
        } elseif (is_array($value) && isset($value['tmp_name'])) {
            $path = $value['tmp_name'];
        }

        if (!$path || !file_exists($path)) {
            return false;
        }

        $imageSize = getimagesize($path);
        if ($imageSize === false) {
            return false;
        }

        [$width, $height] = $imageSize;

        // Must be square
        if ($width !== $height) {
            return false;
        }

        // Min 100x100, max 2000x2000
        return $width >= 100 && $width <= 2000;
    }

    /**
     * Validate PDF file signature.
     */
    public static function validatePdfSignature(mixed $value, string $propertyName): bool
    {
        $path = null;

        if ($value instanceof UploadedFile) {
            $path = $value->getRealPath();
        } elseif (is_array($value) && isset($value['tmp_name'])) {
            $path = $value['tmp_name'];
        }

        if (!$path || !file_exists($path)) {
            return false;
        }

        // Check PDF signature (%PDF-)
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 5);
        fclose($handle);

        return $header === '%PDF-';
    }
}

