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
     * @throws InvalidArgumentException If file doesn't exist, has unsupported format or parsing fails
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
            'yaml', 'yml' => self::loadYamlFile($filePath),
            default => throw new InvalidArgumentException(
                'Unsupported file format: ' . $extension . '. Only XML, JSON and YAML are supported.'
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
            $error = json_last_error_msg();
            throw new InvalidArgumentException(
                'Failed to parse JSON file: ' . $filePath . '. Error: ' . $error
            );
        }

        return is_array($result) ? $result : [];
    }

    /**
     * Load and parse an XML file to array.
     *
     * The root element name is preserved as the top-level key in the returned array.
     * For example, <VitaCost><ConstructionSite>...</ConstructionSite></VitaCost>
     * will return ['VitaCost' => ['ConstructionSite' => [...]]].
     *
     * This method first tries the native SimpleXML method. If that fails (e.g., due to
     * invalid XML with multiple root elements), it falls back to a custom parsing method.
     *
     * @param string $filePath Path to XML file
     * @return array<string, mixed>
     * @throws InvalidArgumentException If XML parsing fails
     */
    private static function loadXmlFile(string $filePath): array
    {
        // Try native XML loading first (standard SimpleXML)
        try {
            return self::loadXmlFileWithNativeMethod($filePath);
        } catch (InvalidArgumentException $e) {
            // If native loading fails, try custom method (e.g., for invalid XML with multiple roots)
            return self::loadXmlFileWithCustomMethod($filePath);
        }
    }

    /**
     * Load and parse an XML file using the native SimpleXML method.
     *
     * This is the standard XML loading method that uses SimpleXML.
     * The root element name is preserved as the top-level key in the returned array.
     *
     * @param string $filePath Path to XML file
     * @return array<string, mixed>
     * @throws InvalidArgumentException If XML parsing fails
     */
    private static function loadXmlFileWithNativeMethod(string $filePath): array
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

        // Get the root element name
        $rootElementName = $xml->getName();

        $jsonString = json_encode($xml);
        if (false === $jsonString) {
            throw new InvalidArgumentException('Failed to encode XML to JSON: ' . $filePath);
        }

        $result = json_decode($jsonString, true);

        // Wrap the result with the root element name to preserve it
        if (is_array($result)) {
            return [$rootElementName => $result];
        }

        return [];
    }

    /**
     * Load and parse an XML file using a custom method.
     *
     * This handles "invalid" XML files that cannot be parsed by SimpleXML,
     * such as files with multiple root elements.
     * Each root element is preserved as a top-level key in the returned array.
     *
     * For example:
     * <LVDATA><LV>...</LV></LVDATA>
     * <POSDATA><POS>...</POS></POSDATA>
     *
     * will return:
     * [
     *   'LVDATA' => ['LV' => [...]],
     *   'POSDATA' => ['POS' => [...]]
     * ]
     *
     * @param string $filePath Path to XML file
     * @return array<string, mixed>
     * @throws InvalidArgumentException If XML parsing fails
     */
    private static function loadXmlFileWithCustomMethod(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new InvalidArgumentException('Failed to read file: ' . $filePath);
        }

        // Clean up content: remove XML declarations, comments, and invalid characters
        $trimmedContent = trim($content);

        // Remove NULL bytes (0x0) which are invalid in XML
        $withoutNullBytes = str_replace("\0", '', $trimmedContent);

        // Remove XML declaration
        $withoutDeclaration = preg_replace('/<' . '?xml[^?]*?' . '>/i', '', $withoutNullBytes);
        if (null === $withoutDeclaration) {
            throw new InvalidArgumentException('Failed to process XML content: ' . $filePath);
        }

        // Remove comments
        $withoutComments = preg_replace('/<!--.*?-->/s', '', $withoutDeclaration);
        if (null === $withoutComments) {
            throw new InvalidArgumentException('Failed to process XML content: ' . $filePath);
        }

        $cleanedContent = trim($withoutComments);

        // Wrap content in a temporary root element to make it valid XML
        $xmlDeclaration = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
        $wrappedContent = $xmlDeclaration . '<root>' . $cleanedContent . '</root>';

        // Suppress errors and warnings
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            $xml = simplexml_load_string($wrappedContent);
        } finally {
            restore_error_handler();
        }

        if (false === $xml) {
            throw new InvalidArgumentException('Failed to parse XML file with custom method: ' . $filePath);
        }

        $result = [];

        // Iterate through each child (original root element)
        foreach ($xml->children() as $child) {
            $rootElementName = $child->getName();

            $jsonString = json_encode($child);
            if (false === $jsonString) {
                throw new InvalidArgumentException('Failed to encode XML element to JSON: ' . $rootElementName);
            }

            $childArray = json_decode($jsonString, true);

            // Store with root element name as key
            if (is_array($childArray)) {
                $result[$rootElementName] = $childArray;
            }
        }

        return $result;
    }

    /**
     * Load and parse a YAML file to array.
     *
     * @param string $filePath Path to YAML file
     * @return array<string, mixed>
     * @throws InvalidArgumentException If YAML parsing fails
     */
    private static function loadYamlFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if (false === $content) {
            throw new InvalidArgumentException('Failed to read file: ' . $filePath);
        }

        $converter = new \event4u\DataHelpers\Converters\YamlConverter();
        return $converter->toArray($content);
    }
}

