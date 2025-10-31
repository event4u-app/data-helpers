<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validation attribute: Value must be a valid file (custom callback validation).
 *
 * This attribute allows custom validation logic via callback.
 * Use this when you need framework-agnostic file validation.
 *
 * Examples:
 * ```php
 * use Illuminate\Http\UploadedFile;
 *
 * class UploadDto extends LiteDto
 * {
 *     public function __construct(
 *         // Check if value is an uploaded file
 *         #[FileCallback(fn($value) => $value instanceof UploadedFile)]
 *         public readonly mixed $document,
 *
 *         // Check file size and type
 *         #[FileCallback(fn($value) =>
 *             $value instanceof UploadedFile &&
 *             $value->getSize() <= 10240 * 1024 && // 10MB
 *             in_array($value->getClientOriginalExtension(), ['pdf', 'doc', 'docx'])
 *         )]
 *         public readonly mixed $document,
 *
 *         // Check if file is an image
 *         #[FileCallback(fn($value) =>
 *             $value instanceof UploadedFile &&
 *             str_starts_with($value->getMimeType(), 'image/')
 *         )]
 *         public readonly mixed $avatar,
 *     ) {}
 * }
 * ```
 *
 * Example with Symfony:
 * ```php
 * use Symfony\Component\HttpFoundation\File\UploadedFile;
 *
 * class UploadDto extends LiteDto
 * {
 *     public function __construct(
 *         #[FileCallback(fn($value) => $value instanceof UploadedFile && $value->isValid())]
 *         public readonly mixed $document,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class FileCallback implements ValidationAttribute
{
    /** @param callable $callback Callback to check file validity: fn(mixed $value, string $propertyName): bool */
    public function __construct(
        public readonly mixed $callback,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        if (!is_callable($this->callback)) {
            return true;
        }

        return (bool)($this->callback)($value, $propertyName);
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s must be a valid file.', $propertyName);
    }
}
