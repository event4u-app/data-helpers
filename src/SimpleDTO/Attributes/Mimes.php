<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: File must have one of the given MIME types (by extension).
 *
 * Example:
 * ```php
 * class DocumentDTO extends SimpleDTO
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
class Mimes implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /**
     * @param array<string> $types Allowed file extensions
     */
    public function __construct(
        public readonly array $types,
    ) {}

    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        return 'mimes:' . implode(',', $this->types);
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */
    public function message(): ?string
    {
        $types = implode(', ', $this->types);
        return "The attribute must be a file of type: {$types}.";
    }

    /**
     * Get Symfony constraint.
     *
     * @return Constraint
     */
    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

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
                default => "application/{$ext}",
            };
        }

        return new Assert\File(
            mimeTypes: $mimeTypes
        );
    }
}

