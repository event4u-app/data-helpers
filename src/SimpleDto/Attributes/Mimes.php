<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validation attribute: File must have one of the given MIME types (by extension).
 *
 * Example:
 * ```php
 * class DocumentDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Mimes(['pdf', 'doc', 'docx'])]
 *         public readonly mixed $document,
 *
 *         #[Mimes(['jpg', 'png', 'gif'])]
 *         public readonly mixed $image,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Mimes implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /** @param array<string> $types Allowed file extensions */
    public function __construct(
        public readonly array $types,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'mimes:' . implode(',', $this->types);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $types = implode(', ', $this->types);
        return sprintf('The attribute must be a file of type: %s.', $types);
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
        if (!is_object($value)) {
            return false;
        }

        $className = $value::class;
        if (!str_contains($className, 'UploadedFile')) {
            return false;
        }

        // Get file extension
        $extension = null;
        if (method_exists($value, 'getClientOriginalExtension')) {
            $extension = strtolower($value->getClientOriginalExtension());
        } elseif (method_exists($value, 'guessExtension')) {
            $extension = strtolower($value->guessExtension() ?? '');
        }

        if (null === $extension || '' === $extension) {
            return false;
        }

        // Check if extension is in allowed types
        $normalizedTypes = array_map('strtolower', $this->types);
        return in_array($extension, $normalizedTypes, true);
    }

    /**
     * Get validation error message.
     *
     * @param string $propertyName The name of the property being validated
     * @return string The error message
     */
    public function getErrorMessage(string $propertyName): string
    {
        $types = implode(', ', $this->types);
        return sprintf('The %s must be a file of type: %s.', $propertyName, $types);
    }

    /** Get Symfony constraint. */
    public function constraint(): object
    {
        // Convert extensions to MIME types
        $mimeTypes = [];
        foreach ($this->types as $ext) {
            $mimeTypes[] = match ($ext) {
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                'txt' => 'text/plain',
                'csv' => 'text/csv',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                '7z' => 'application/x-7z-compressed',
                'tar' => 'application/x-tar',
                'gz' => 'application/gzip',
                default => 'application/' . $ext,
            };
        }

        return $this->createConstraint(
            "\\Symfony\\Component\\Validator\\Constraints\\File",
            ['mimeTypes' => $mimeTypes]
        );
    }
}
