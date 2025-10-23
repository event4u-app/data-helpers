<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be an image file.
 *
 * Example:
 * ```php
 * class ProfileDTO extends SimpleDTO
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
class Image implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

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

    /** Get Symfony constraint. */
    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

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

        return new Assert\Image(
            maxSize: null !== $this->maxSize && 0 < $this->maxSize ? $this->maxSize * 1024 : null,
            mimeTypes: $mimeTypes,
            minWidth: null !== $this->minWidth && 0 < $this->minWidth ? $this->minWidth : null,
            maxWidth: null !== $this->maxWidth && 0 < $this->maxWidth ? $this->maxWidth : null,
            maxHeight: null !== $this->maxHeight && 0 < $this->maxHeight ? $this->maxHeight : null,
            minHeight: null !== $this->minHeight && 0 < $this->minHeight ? $this->minHeight : null,
        );
    }
}
