<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validation attribute: Value must be an image file.
 *
 * Example:
 * ```php
 * class ProfileDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Image]
 *         public readonly mixed $avatar,
 *
 *         #[Image(mimes: ['jpg', 'png'], maxSize: 2048)]
 *         public readonly mixed $photo,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Image implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

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

    /**
     * Convert to Laravel validation rule.
     *
     * @return array<string>
     */
    public function rule(): array
    {
        $rules = ['image'];

        if (null !== $this->mimes) {
            $rules[] = 'mimes:' . implode(',', $this->mimes);
        }

        if (null !== $this->maxSize) {
            $rules[] = 'max:' . $this->maxSize;
        }

        $dimensions = [];
        if (null !== $this->minWidth) {
            $dimensions[] = 'min_width=' . $this->minWidth;
        }
        if (null !== $this->maxWidth) {
            $dimensions[] = 'max_width=' . $this->maxWidth;
        }
        if (null !== $this->minHeight) {
            $dimensions[] = 'min_height=' . $this->minHeight;
        }
        if (null !== $this->maxHeight) {
            $dimensions[] = 'max_height=' . $this->maxHeight;
        }

        if ([] !== $dimensions) {
            $rules[] = 'dimensions:' . implode(',', $dimensions);
        }

        return $rules;
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return 'The attribute must be an image.';
    }

    /**
     * Validate the value using Plain PHP.
     *
     * @param mixed $value The value to validate
     * @param string $propertyName The name of the property being validated
     * @return bool True if valid, false otherwise
     */
    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value) {
            return true; // Null values are handled by Required attribute
        }

        // Check if it's an uploaded file object
        if (is_object($value)) {
            $className = $value::class;
            if (!str_contains($className, 'UploadedFile')) {
                return false;
            }

            // Get file path for validation
            $filePath = null;
            if (method_exists($value, 'getRealPath')) {
                $filePath = $value->getRealPath();
            } elseif (method_exists($value, 'getPathname')) {
                $filePath = $value->getPathname();
            }

            if (null === $filePath || !file_exists($filePath)) {
                return false;
            }

            // Validate it's a valid image using getimagesize
            $imageInfo = @getimagesize($filePath);
            if (false === $imageInfo) {
                return false; // Not a valid image
            }

            [$width, $height, $type] = $imageInfo;

            // Validate dimensions
            if (null !== $this->minWidth && $width < $this->minWidth) {
                return false;
            }
            if (null !== $this->maxWidth && $width > $this->maxWidth) {
                return false;
            }
            if (null !== $this->minHeight && $height < $this->minHeight) {
                return false;
            }
            if (null !== $this->maxHeight && $height > $this->maxHeight) {
                return false;
            }

            // Validate file size
            if (null !== $this->maxSize && method_exists($value, 'getSize')) {
                $sizeInKb = $value->getSize() / 1024;
                if ($sizeInKb > $this->maxSize) {
                    return false;
                }
            }

            // Validate MIME types if specified
            if (null !== $this->mimes) {
                $imageMimeType = image_type_to_mime_type($type);
                $allowedMimeTypes = [];
                foreach ($this->mimes as $mime) {
                    $allowedMimeTypes[] = match ($mime) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'bmp' => 'image/bmp',
                        'svg' => 'image/svg+xml',
                        'webp' => 'image/webp',
                        default => 'image/' . $mime,
                    };
                }

                if (!in_array($imageMimeType, $allowedMimeTypes, true)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get validation error message.
     *
     * @param string $propertyName The name of the property being validated
     * @return string The error message
     */
    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s must be a valid image.', $propertyName);
    }

    /** Get Symfony constraint. */
    public function constraint(): object
    {
        $mimeTypes = null;
        if (null !== $this->mimes) {
            // Convert short names to MIME types
            $mimeTypes = [];
            foreach ($this->mimes as $mime) {
                $mimeTypes[] = match ($mime) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => 'image/' . $mime,
                };
            }
        } else {
            // Default image MIME types
            $mimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/bmp',
                'image/svg+xml',
                'image/webp',
            ];
        }

        return $this->createConstraint(
            "\\Symfony\\Component\\Validator\\Constraints\\Image",
            [
                'maxSize' => null !== $this->maxSize && 0 < $this->maxSize ? $this->maxSize * 1024 : null,
                'mimeTypes' => $mimeTypes,
                'minWidth' => null !== $this->minWidth && 0 < $this->minWidth ? $this->minWidth : null,
                'maxWidth' => null !== $this->maxWidth && 0 < $this->maxWidth ? $this->maxWidth : null,
                'maxHeight' => null !== $this->maxHeight && 0 < $this->maxHeight ? $this->maxHeight : null,
                'minHeight' => null !== $this->minHeight && 0 < $this->minHeight ? $this->minHeight : null,
            ]
        );
    }
}
