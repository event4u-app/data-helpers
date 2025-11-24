<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;
use event4u\DataHelpers\SimpleDto\Support\TextSanitizer;

/**
 * Sanitize and normalize text values.
 *
 * This attribute automatically cleans and normalizes text by:
 * - Converting RTF (Rich Text Format) to plain text
 * - Removing or converting HTML tags (optional, default: true)
 * - Normalizing whitespace and line breaks
 * - Removing control characters
 *
 * Can be applied to:
 * - Individual properties (sanitizes that property only)
 * - The entire class (sanitizes all non-numeric string properties)
 *
 * Example:
 * ```php
 * // Apply to specific property
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Sanitize]
 *         public readonly string $description,
 *
 *         #[Sanitize(stripHtml: false)]
 *         public readonly string $htmlContent,
 *     ) {}
 * }
 *
 * // Apply to entire class (all string properties)
 * #[Sanitize]
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,        // Will be sanitized
 *         public readonly string $bio,         // Will be sanitized
 *         public readonly int $age,            // Won't be sanitized (numeric)
 *     ) {}
 * }
 * ```
 *
 * RTF Example:
 * ```php
 * $data = [
 *     'description' => '{\rtf1\ansi\deff0{\fonttbl{\f0\fnil\fcharset0 Arial;}}
 *         \viewkind4\uc1\pard\lang1031\fs20 Einfassungen, Gossen, Einzelabläufe und \line Rinnen \par}'
 * ];
 *
 * $dto = ProductDto::fromArray($data);
 * // $dto->description = "Einfassungen, Gossen, Einzelabläufe und Rinnen"
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
class Sanitize implements TransformAttribute
{
    public function __construct(
        /**
         * Whether to strip HTML tags (default: true).
         * If false, HTML entities will be decoded but tags remain.
         */
        public readonly bool $stripHtml = true,

        /**
         * Whether to decode HTML entities (default: true).
         */
        public readonly bool $decodeHtmlEntities = true,

        /**
         * Whether to normalize whitespace (default: true).
         * Converts multiple spaces/tabs to single space, normalizes line breaks.
         */
        public readonly bool $normalizeWhitespace = true,

        /**
         * Whether to remove control characters (default: true).
         * Removes non-printable characters except newlines and tabs.
         */
        public readonly bool $removeControlChars = true,
    ) {}

    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return TextSanitizer::sanitize(
            $value,
            $this->stripHtml,
            $this->decodeHtmlEntities,
            $this->normalizeWhitespace,
            $this->removeControlChars
        );
    }
}

