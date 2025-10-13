<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

use InvalidArgumentException;

/**
 * Utility class for loading files (JSON, XML) as arrays.
 */
final class FileLoader
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * Load a file (JSON or XML) and return its content as an array.
     *
     * @param string $filePath Path to the file
     * @return array<string, mixed> The file content as an associative array
     * @throws InvalidArgumentException If file doesn't exist, has unsupported format, or parsing fails
     */
    public static function loadAsArray(string $filePath): array
    {
        // Check cache first (using realpath to normalize path)
        $realPath = realpath($filePath);
        if (false !== $realPath && isset(self::$cache[$realPath])) {
            return self::$cache[$realPath];
        }

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('File not found: ' . $filePath);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $result = match ($extension) {
            'json' => self::loadJsonFile($filePath),
            'xml' => self::loadXmlFile($filePath),
            default => throw new InvalidArgumentException(
                'Unsupported file format: ' . $extension . '. Only XML and JSON are supported.'
            ),
        };

        // Cache the result
        if (false !== $realPath) {
            self::$cache[$realPath] = $result;
        }

        return $result;
    }

    /**
     * Load and parse a JSON file to array.
     *
     * @param string $filePath Path to JSON file
     * @return array<string, mixed>
     * @throws InvalidArgumentException If JSON parsing fails
     */
    private static function loadJsonFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if (false === $content) {
            throw new InvalidArgumentException('Failed to read file: ' . $filePath);
        }

        $result = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(
                'Failed to parse JSON file: ' . $filePath . '. Error: ' . json_last_error_msg()
            );
        }

        return is_array($result) ? $result : [];
    }

    /**
     * Load and parse an XML file to array.
     *
     * @param string $filePath Path to XML file
     * @return array<string, mixed>
     * @throws InvalidArgumentException If XML parsing fails
     */
    private static function loadXmlFile(string $filePath): array
    {
        // Suppress errors and warnings to prevent ErrorException in Laravel
        set_error_handler(static function (): bool {
            return true; // Suppress the error
        });

        try {
            $xml = simplexml_load_file($filePath);
        } finally {
            // Always restore the previous error handler
            restore_error_handler();
        }

        if (false === $xml) {
            throw new InvalidArgumentException('Failed to parse XML file: ' . $filePath);
        }

        $jsonString = json_encode($xml);
        if (false === $jsonString) {
            throw new InvalidArgumentException('Failed to encode XML to JSON: ' . $filePath);
        }

        $result = json_decode($jsonString, true);

        return is_array($result) ? $result : [];
    }
}

