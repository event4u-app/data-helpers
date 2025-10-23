<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SimpleXMLElement;

/**
 * Trait for comparing DTOs with different data sources.
 *
 * Provides diff() method to compare the current DTO with:
 * - Arrays
 * - JSON strings
 * - Other DTOs
 * - Models/Entities (Laravel/Doctrine)
 * - XML strings
 *
 * Example usage:
 *   $user = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);
 *   $diff = $user->diff(['name' => 'Jane', 'email' => 'john@example.com']);
 *   // Returns: ['name' => ['dto' => 'John', 'data' => 'Jane']]
 */
trait SimpleDTODiffTrait
{
    /**
     * Compare the current DTO with another data source.
     *
     * Supports multiple input formats:
     * - Array: ['key' => 'value']
     * - JSON: '{"key": "value"}'
     * - DTO: Another DTO instance
     * - Model/Entity: Laravel Model or Doctrine Entity
     * - XML: '<root><key>value</key></root>'
     *
     * @param mixed $data The data to compare with
     * @param bool $ignoreNonExistingKeys If true, ignore keys that don't exist in the input data
     * @param bool $nested If true, perform nested comparison for arrays and objects
     * @return array<string, array{dto: mixed, data: mixed}> Array of differences with 'dto' and 'data' values (keys use dot notation)
     */
    public function diff(mixed $data, bool $ignoreNonExistingKeys = false, bool $nested = true): array
    {
        // Convert input data to array
        $compareArray = $this->convertToArray($data);

        // Get current DTO data as array
        $currentArray = $this->toArray();

        // Perform comparison
        return $this->compareArrays($currentArray, $compareArray, $ignoreNonExistingKeys, $nested);
    }

    /**
     * Convert various data formats to array.
     *
     * @param mixed $data The data to convert
     * @return array<string, mixed>
     */
    private function convertToArray(mixed $data): array
    {
        // Already an array
        if (is_array($data)) {
            /** @var array<string, mixed> $data */
            return $data;
        }

        // JSON string
        if (is_string($data)) {
            // Try to decode as JSON
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                /** @var array<string, mixed> $decoded */
                return $decoded;
            }

            // Try to parse as XML
            if (str_starts_with(trim($data), '<')) {
                return $this->xmlToArray($data);
            }

            throw new InvalidArgumentException('String data must be valid JSON or XML');
        }

        // DTO instance
        if ($data instanceof DTOInterface) {
            return $data->toArray();
        }

        // Laravel Model
        if (is_object($data) && method_exists($data, 'toArray')) {
            return $data->toArray();
        }

        // Doctrine Entity or any object with getters
        if (is_object($data)) {
            return $this->objectToArray($data);
        }

        throw new InvalidArgumentException(
            sprintf('Unsupported data type: %s', get_debug_type($data))
        );
    }

    /**
     * Convert XML string to array.
     *
     * @param string $xml The XML string
     * @return array<string, mixed>
     */
    private function xmlToArray(string $xml): array
    {
        try {
            $element = new SimpleXMLElement($xml);
            $result = $this->simpleXmlToArray($element);
            /** @var array<string, mixed> $result */
            return $result;
        } catch (Exception $exception) {
            throw new InvalidArgumentException(
                'Invalid XML: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Convert SimpleXMLElement to array recursively.
     *
     * @param SimpleXMLElement $element The XML element
     * @return array<string|int, mixed>|string
     */
    private function simpleXmlToArray(SimpleXMLElement $element): array|string
    {
        $result = [];

        // Get attributes
        foreach ($element->attributes() as $key => $value) {
            $result['@' . $key] = (string)$value;
        }

        // Get children
        $hasChildren = false;
        foreach ($element->children() as $key => $child) {
            $hasChildren = true;
            $childValue = $this->simpleXmlToArray($child);

            // Handle multiple elements with same name
            if (isset($result[$key])) {
                if (!is_array($result[$key]) || !isset($result[$key][0])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $childValue;
            } else {
                $result[$key] = $childValue;
            }
        }

        // If no children and no attributes, return text content as string
        if (!$hasChildren && [] === $result) {
            return (string)$element;
        }

        return $result;
    }

    /**
     * Convert object to array using reflection.
     *
     * @param object $object The object to convert
     * @return array<string, mixed>
     */
    private function objectToArray(object $object): array
    {
        /** @var array<string, mixed> $result */
        $result = [];
        $reflection = new ReflectionClass($object);

        // Try getters first
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();

            // Check for getter methods (get*, is*, has*)
            if (
                (str_starts_with($methodName, 'get') ||
                 str_starts_with($methodName, 'is') ||
                 str_starts_with($methodName, 'has')) &&
                $reflectionMethod->getNumberOfRequiredParameters() === 0
            ) {
                $propertyName = lcfirst(
                    substr($methodName, str_starts_with($methodName, 'is') || str_starts_with(
                        $methodName,
                        'has'
                    ) ? 2 : 3)
                );

                if ('' !== $propertyName) {
                    try {
                        $result[$propertyName] = $reflectionMethod->invoke($object);
                    } catch (Exception) {
                        // Skip if getter throws exception
                    }
                }
            }
        }

        // If no getters found, use public properties
        if (empty($result)) {
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                $result[$reflectionProperty->getName()] = $reflectionProperty->getValue($object);
            }
        }

        return $result;
    }

    /**
     * Compare two arrays and return differences.
     *
     * @param array<string|int, mixed> $current Current array (DTO data)
     * @param array<string|int, mixed> $compare Array to compare with
     * @param bool $ignoreNonExistingKeys If true, ignore keys that don't exist in compare array
     * @param bool $nested If true, perform nested comparison
     * @param string $prefix Key prefix for nested keys (used internally)
     * @return array<string, array{dto: mixed, data: mixed}>
     */
    private function compareArrays(
        array $current,
        array $compare,
        bool $ignoreNonExistingKeys,
        bool $nested,
        string $prefix = ''
    ): array {
        $differences = [];

        // Check all keys in current array
        foreach ($current as $key => $currentValue) {
            $fullKey = '' === $prefix ? (string)$key : $prefix . '.' . $key;

            // Key doesn't exist in compare array
            if (!array_key_exists($key, $compare)) {
                if (!$ignoreNonExistingKeys) {
                    $differences[$fullKey] = [
                        'dto' => $currentValue,
                        'data' => null,
                    ];
                }
                continue;
            }

            $compareValue = $compare[$key];

            // Both values are arrays and nested comparison is enabled
            if ($nested && is_array($currentValue) && is_array($compareValue)) {
                $nestedDiff = $this->compareArrays(
                    $currentValue,
                    $compareValue,
                    $ignoreNonExistingKeys,
                    $nested,
                    $fullKey
                );
                $differences = array_merge($differences, $nestedDiff);
                continue;
            }

            // Values are different
            if ($currentValue !== $compareValue) {
                $differences[$fullKey] = [
                    'dto' => $currentValue,
                    'data' => $compareValue,
                ];
            }
        }

        // Check for new keys in compare array (only if not ignoring)
        if (!$ignoreNonExistingKeys) {
            foreach ($compare as $key => $compareValue) {
                if (!array_key_exists($key, $current)) {
                    $fullKey = '' === $prefix ? (string)$key : $prefix . '.' . $key;
                    $differences[$fullKey] = [
                        'dto' => null,
                        'data' => $compareValue,
                    ];
                }
            }
        }

        return $differences;
    }
}
