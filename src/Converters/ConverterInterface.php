<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Converters;

use InvalidArgumentException;

/**
 * Interface for bidirectional format converters.
 *
 * Converters handle conversion between arrays and various formats (JSON, XML, YAML, CSV, etc.).
 * They support both serialization (array to format) and deserialization (format to array).
 */
interface ConverterInterface
{
    /**
     * Convert a string in the specific format to an array.
     *
     * @param string $data The data in the specific format
     * @return array<string, mixed> The converted array
     * @throws InvalidArgumentException If the data cannot be parsed
     */
    public function toArray(string $data): array;

    /**
     * Convert an array to a string in the specific format.
     *
     * @param array<string, mixed> $data The data to convert
     * @return string The formatted string
     */
    public function fromArray(array $data): string;

    /**
     * Get the content type for this converter.
     *
     * @return string The content type (e.g., 'application/json', 'application/xml')
     */
    public function getContentType(): string;

    /**
     * Get the file extension for this converter.
     *
     * @return string The file extension (e.g., 'json', 'xml')
     */
    public function getFileExtension(): string;
}
