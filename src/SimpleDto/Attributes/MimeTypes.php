<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validation attribute: File must have one of the given MIME types (by actual MIME type).
 *
 * More strict than Mimes - checks actual MIME type, not just extension.
 *
 * Example:
 * ```php
 * class DocumentDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[MimeTypes(['application/pdf', 'application/msword'])]
 *         public readonly mixed $document,
 *
 *         #[MimeTypes(['image/jpeg', 'image/png', 'image/gif'])]
 *         public readonly mixed $image,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MimeTypes implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /** @param array<string> $types Allowed MIME types */
    public function __construct(
        public readonly array $types,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'mimetypes:' . implode(',', $this->types);
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

        // Get file path for MIME type detection
        $filePath = null;
        if (method_exists($value, 'getRealPath')) {
            $filePath = $value->getRealPath();
        } elseif (method_exists($value, 'getPathname')) {
            $filePath = $value->getPathname();
        }

        if (null === $filePath || !file_exists($filePath)) {
            return false;
        }

        // Detect MIME type using fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (false === $finfo) {
            return false;
        }

        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (false === $mimeType) {
            return false;
        }

        // Check if MIME type is in allowed types
        return in_array($mimeType, $this->types, true);
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
        return $this->createConstraint(
            "\Symfony\Component\Validator\Constraints\File",
            [
                'mimeTypes' => $this->types,
            ]
        );
    }
}
