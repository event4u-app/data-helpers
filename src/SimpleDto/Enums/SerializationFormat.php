<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Enums;

use event4u\DataHelpers\SimpleDto\Config\SerializerOptions;
use event4u\DataHelpers\SimpleDto\Serializers\CsvSerializer;
use event4u\DataHelpers\SimpleDto\Serializers\SerializerInterface;
use event4u\DataHelpers\SimpleDto\Serializers\XmlSerializer;
use event4u\DataHelpers\SimpleDto\Serializers\YamlSerializer;
use RuntimeException;

/**
 * Serialization format enum for Dto serialization.
 *
 * Provides type-safe serialization formats for SimpleDtos.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\Enums\SerializationFormat;
 *
 * // Serialize to format
 * $xml = $dto->serializeTo(SerializationFormat::Xml);
 *
 * // Get serializer
 * $serializer = SerializationFormat::Json->getSerializer();
 *
 * // Get file extension
 * $ext = SerializationFormat::Yaml->getFileExtension();
 * // Result: 'yaml'
 *
 * // Parse from string
 * $format = SerializationFormat::fromString('json');
 * ```
 */
enum SerializationFormat: string
{
    case Json = 'json';
    case Xml = 'xml';
    case Yaml = 'yaml';
    case Csv = 'csv';

    /**
     * Get the serializer instance for this format.
     *
     * @param SerializerOptions|null $options Format-specific options (uses default if null)
     *
     * @return SerializerInterface The serializer instance
     */
    public function getSerializer(?SerializerOptions $options = null): SerializerInterface
    {
        $options ??= SerializerOptions::default();

        return match ($this) {
            self::Json => throw new RuntimeException('JSON serialization is built-in, use toJson() method'),
            self::Xml => new XmlSerializer($options->rootElement, $options->xmlVersion, $options->encoding),
            self::Yaml => new YamlSerializer($options->indent),
            self::Csv => new CsvSerializer(
                $options->includeHeaders,
                $options->delimiter,
                $options->enclosure,
                $options->escape
            ),
        };
    }

    /**
     * Get the file extension for this format.
     *
     * @return string The file extension (without dot)
     */
    public function getFileExtension(): string
    {
        return $this->value;
    }

    /**
     * Get the MIME type for this format.
     *
     * @return string The MIME type
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::Json => 'application/json',
            self::Xml => 'application/xml',
            self::Yaml => 'application/x-yaml',
            self::Csv => 'text/csv',
        };
    }

    /**
     * Parse a serialization format from a string.
     *
     * @param string $format The format string (e.g., 'json', 'xml')
     *
     * @return self|null The serialization format or null if invalid
     */
    public static function fromString(string $format): ?self
    {
        return match (strtolower($format)) {
            'json' => self::Json,
            'xml' => self::Xml,
            'yaml', 'yml' => self::Yaml,
            'csv' => self::Csv,
            default => null,
        };
    }

    /**
     * Parse from file extension.
     *
     * @param string $extension The file extension (with or without dot)
     *
     * @return self|null The serialization format or null if invalid
     */
    public static function fromExtension(string $extension): ?self
    {
        $extension = ltrim($extension, '.');

        return self::fromString($extension);
    }

    /**
     * Get all available serialization formats.
     *
     * @return array<string> Array of format strings
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a string is a valid serialization format.
     *
     * @param string $format The format string to check
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $format): bool
    {
        return self::fromString($format) instanceof \event4u\DataHelpers\SimpleDto\Enums\SerializationFormat;
    }
}
