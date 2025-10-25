<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\Helpers\DotPathHelper;
use event4u\DataHelpers\Support\ArrayableHelper;
use event4u\DataHelpers\Support\CollectionHelper;
use event4u\DataHelpers\Support\EntityHelper;
use JsonSerializable;
use SimpleXMLElement;
use stdClass;

class DataAccessor
{
    /** @var array<int|string, mixed> */
    private array $data;

    /**
     * Static path cache for compiled access paths.
     * Stores pre-computed path information to avoid repeated parsing.
     *
     * @var array<string, array{segments: array<int, string>, hasWildcard: bool}>
     */
    private static array $pathCache = [];

    /**
     * Create a new DataAccessor instance (factory method).
     *
     * @param mixed $input Initial input (array, DTO, Model, Collection, JSON, XML, scalar)
     * @return self
     */
    public static function make(mixed $input): self
    {
        return new self($input);
    }

    /** @param mixed $input Initial input (array, DTO, Model, Collection, JSON, XML, scalar) */
    public function __construct(mixed $input)
    {
        if (is_string($input)) {
            // Try JSON
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->data = is_array($decoded) ? $decoded : [$decoded];

                return;
            }

            // Try XML (avoid double encode/decode when possible)
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (false !== $xml) {
                // Convert SimpleXMLElement to array (ensure array shape for internal storage)
                $xmlConverted = self::xmlToArray($xml);
                $this->data = is_array($xmlConverted) ? $xmlConverted : [$xmlConverted];

                return;
            }

            // Fallback: simple string
            $this->data = [$input];

            return;
        }

        if (is_array($input)) {
            $this->data = $input;

            return;
        }

        if (CollectionHelper::isCollection($input)) {
            $this->data = CollectionHelper::toArray($input);

            return;
        }

        if (EntityHelper::isEntity($input)) {
            $this->data = EntityHelper::toArray($input);

            return;
        }

        // Fallback for Arrayable interface
        if (ArrayableHelper::isArrayable($input)) {
            $this->data = ArrayableHelper::toArray($input);

            return;
        }

        if ($input instanceof JsonSerializable) {
            $this->data = (array)$input->jsonSerialize();

            return;
        }

        if (is_object($input)) {
            $this->data = self::objectToArrayFast($input);

            return;
        }

        // Scalars
        $this->data = [$input];
    }

    /**
     * Access data by dot-notation.
     *
     * Example:
     *   get("user.address.city")
     *   get("users.*.name") -> returns array keyed by full dot path
     *   get("orders.*.items.*.id") -> nested wildcards supported
     *
     * @param string $path Dot-notation path
     * @param mixed $default Default if path not found
     */
    public function get(string $path, mixed $default = null): mixed
    {
        // Use static path cache for compiled path information
        $pathInfo = $this->getPathInfo($path);

        $results = $pathInfo['hasWildcard']
            ? $this->extract($this->data, $pathInfo['segments'], '', 0, count($pathInfo['segments']))
            : $this->extractSimple($this->data, $pathInfo['segments']);

        if (null === $results) {
            return $default;
        }

        if ($pathInfo['hasWildcard']) {
            return $results; // always array with dot-paths
        }

        // Non-wildcard paths: unwrap single-element array
        if (is_array($results) && count($results) === 1) {
            return reset($results);
        }

        return $results;
    }

    /**
     * Check if a path exists in the data.
     *
     * Returns true if the path exists, even if the value is null.
     * For wildcard paths, returns true if at least one element has the property.
     *
     * Example:
     *   exists("user.name") -> true if user.name exists (even if null)
     *   exists("users.*.email") -> true if at least one user has email property
     *
     * @param string $path Dot-notation path
     */
    public function exists(string $path): bool
    {
        // Use static path cache for compiled path information
        $pathInfo = $this->getPathInfo($path);

        $results = $pathInfo['hasWildcard']
            ? $this->extract($this->data, $pathInfo['segments'], '', 0, count($pathInfo['segments']))
            : $this->extractSimple($this->data, $pathInfo['segments']);

        // If results is null, path doesn't exist
        if (null === $results) {
            return false;
        }

        // For non-wildcard paths, extractSimple returns array with single element or null
        // If we got here, the path exists (even if value is null)
        if (!$pathInfo['hasWildcard']) {
            return true;
        }

        // For wildcard paths, check if at least one element exists
        // extract() returns empty array if no matches found, null if path invalid
        return [] !== $results;
    }

    /**
     * Get compiled path information from static cache.
     *
     * @return array{segments: array<int, string>, hasWildcard: bool}
     */
    private function getPathInfo(string $path): array
    {
        if (isset(self::$pathCache[$path])) {
            return self::$pathCache[$path];
        }

        $segments = DotPathHelper::segments($path);
        $hasWildcard = DotPathHelper::containsWildcard($path);

        return self::$pathCache[$path] = [
            'segments' => $segments,
            'hasWildcard' => $hasWildcard,
        ];
    }



    /**
     * Get value as string.
     *
     * @param string $path Dot-notation path
     * @param null|string $default Default if path not found
     */
    public function getString(string $path, ?string $default = null): ?string
    {
        $value = $this->get($path);

        if (null === $value) {
            return $default;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return $default;
    }

    /**
     * Get value as integer.
     *
     * @param string $path Dot-notation path
     * @param null|int $default Default if path not found
     */
    public function getInt(string $path, ?int $default = null): ?int
    {
        $value = $this->get($path);

        if (null === $value) {
            return $default;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        return $default;
    }

    /**
     * Get value as float.
     *
     * @param string $path Dot-notation path
     * @param null|float $default Default if path not found
     */
    public function getFloat(string $path, ?float $default = null): ?float
    {
        $value = $this->get($path);

        if (null === $value) {
            return $default;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        return $default;
    }

    /**
     * Get value as boolean.
     *
     * @param string $path Dot-notation path
     * @param null|bool $default Default if path not found
     */
    public function getBool(string $path, ?bool $default = null): ?bool
    {
        $value = $this->get($path);

        if (null === $value) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        // Convert common truthy/falsy values
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (bool)$value;
        }

        return $default;
    }

    /**
     * Get value as array.
     *
     * @param string $path Dot-notation path
     * @param null|array<int|string, mixed> $default Default if path not found
     * @return null|array<int|string, mixed>
     */
    public function getArray(string $path, ?array $default = null): ?array
    {
        $value = $this->get($path);

        if (null === $value) {
            return $default;
        }

        if (is_array($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Fast extraction for non-wildcard paths (no wildcard overhead).
     *
     * @param array<int, string> $segments
     */
    private function extractSimple(mixed $current, array $segments): mixed
    {
        foreach ($segments as $segment) {
            // Traverse array
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];

                continue;
            }

            // Traverse entity/model
            if (EntityHelper::hasAttribute($current, $segment)) {
                $current = EntityHelper::getAttribute($current, $segment);

                continue;
            }

            // Traverse collection
            if (CollectionHelper::has($current, $segment)) {
                $current = CollectionHelper::get($current, $segment);

                continue;
            }

            // Path not found
            return null;
        }

        return [$current];
    }

    /**
     * Recursive extraction supporting arrays, Models, Collections and wildcards.
     *
     * @param array<int, string> $segments
     * @param int $index Current position in segments array
     * @param int $segmentCount Total number of segments (cached for performance)
     * @return null|array<int|string, mixed>
     */
    private function extract(
        mixed $current,
        array $segments,
        string $prefix = '',
        int $index = 0,
        int $segmentCount = 0,
    ): ?array {
        // Base case: reached end of path
        if ($segmentCount <= $index) {
            return [
                $prefix => $current,
            ];
        }

        // Get current segment using O(1) index access
        $segment = $segments[$index];

        // Wildcard - inline check for performance
        if ('*' === $segment) {
            $originalCurrent = $current;

            if (CollectionHelper::isCollection($current)) {
                $current = CollectionHelper::toArray($current);
                // Free memory: original collection not needed anymore
                unset($originalCurrent);
            } elseif (EntityHelper::isEntity($current)) {
                $current = EntityHelper::getAttributes($current);
                // Free memory: original entity not needed anymore
                unset($originalCurrent);
            }

            if (!is_array($current)) {
                return null;
            }

            return $this->collectFromIterable($current, $segments, $prefix, $index + 1, $segmentCount);
        }

        // Traverse array
        if (is_array($current) && array_key_exists($segment, $current)) {
            // Inline buildPrefix for performance
            $newPrefix = '' === $prefix ? $segment : $prefix . '.' . $segment;

            return $this->extract($current[$segment], $segments, $newPrefix, $index + 1, $segmentCount);
        }

        // Traverse entity/model
        if (EntityHelper::hasAttribute($current, $segment)) {
            $value = EntityHelper::getAttribute($current, $segment);
            // Inline buildPrefix for performance
            $newPrefix = '' === $prefix ? $segment : $prefix . '.' . $segment;

            return $this->extract($value, $segments, $newPrefix, $index + 1, $segmentCount);
        }

        // Traverse collection
        if (CollectionHelper::has($current, $segment)) {
            $value = CollectionHelper::get($current, $segment);
            // Inline buildPrefix for performance
            $newPrefix = '' === $prefix ? $segment : $prefix . '.' . $segment;

            return $this->extract($value, $segments, $newPrefix, $index + 1, $segmentCount);
        }

        return null;
    }

    /**
     * Merge results from iterating wildcard children.
     *
     * @param array<int|string, mixed> $current
     * @param array<int, string> $segments Full segments array
     * @param int $index Current position in segments (after wildcard)
     * @param int $segmentCount Total number of segments (cached)
     * @return array<int|string, mixed>
     */
    private function collectFromIterable(
        array $current,
        array $segments,
        string $prefix,
        int $index,
        int $segmentCount,
    ): array {
        $collected = [];
        foreach ($current as $key => $item) {
            // Inline prefix building for performance (avoid function call overhead)
            $newPrefix = '' === $prefix ? (string)$key : $prefix . '.' . $key;
            $value = $this->extract($item, $segments, $newPrefix, $index, $segmentCount);
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $collected[$k] = $v; // avoid array_merge copies
                }
            }
        }

        return $collected;
    }

    /**
     * Get the data structure with type information as a flat array using dot-notation.
     *
     * Returns an array where keys are dot-notation paths (with wildcards for arrays)
     * and values are type strings (with union types for mixed values).
     *
     * Example:
     *   [
     *     'name' => 'string',
     *     'age' => 'int',
     *     'address' => 'array',
     *     'address.city' => 'string',
     *     'address.zip' => 'int',
     *     'emails' => 'array',
     *     'emails.*' => '\EmailDTO',
     *     'emails.*.email' => 'string',
     *     'emails.*.verified' => 'bool',
     *   ]
     *
     * @return array<string, string>
     */
    public function getStructure(): array
    {
        return $this->extractKeysFlat($this->data);
    }

    /**
     * Get the data structure with type information as a multidimensional array.
     *
     * Returns a nested array structure where leaf values are type strings
     * (with union types for mixed values). Arrays use wildcards.
     *
     * Example:
     *   [
     *     'name' => 'string',
     *     'age' => 'int',
     *     'address' => [
     *       'city' => 'string',
     *       'zip' => 'int',
     *     ],
     *     'emails' => [
     *       '*' => '\EmailDTO',
     *     ],
     *   ]
     *
     * @return array<int|string, mixed>
     */
    public function getStructureMultidimensional(): array
    {
        return $this->extractKeysMultidimensional($this->data);
    }

    /**
     * Extract keys as flat array with dot-notation paths.
     * Uses wildcards for array elements and union types for mixed values.
     *
     * @return array<string, string>
     */
    private function extractKeysFlat(mixed $data, string $prefix = ''): array
    {
        $keys = [];

        if (is_array($data)) {
            // Check if this is a numeric array (list)
            $isNumericArray = array_is_list($data);

            if ($isNumericArray && [] !== $data) {
                // Analyze all elements to find common structure and union types
                $mergedStructure = $this->analyzeArrayElements($data);

                // Add wildcard path for the array elements
                foreach ($mergedStructure as $subKey => $type) {
                    if ('' === $subKey) {
                        // Direct element type
                        $path = '' === $prefix ? '*' : $prefix . '.*';
                        $keys[$path] = $type;
                    } else {
                        // Nested property
                        $path = '' === $prefix ? '*.' . $subKey : $prefix . '.*.' . $subKey;
                        $keys[$path] = $type;
                    }
                }
            } else {
                // Associative array - process each key normally
                foreach ($data as $key => $value) {
                    $path = '' === $prefix ? (string)$key : $prefix . '.' . $key;

                    // Check if value is an object before converting
                    $isObject = is_object($value);
                    $objectClass = $isObject ? '\\' . $value::class : null;

                    // Convert objects to arrays for recursive processing, but preserve nested objects
                    if ($isObject) {
                        $value = $this->objectToArrayPreservingObjects($value);
                    }

                    if (is_array($value) && [] !== $value) {
                        // Mark with class name if it was an object, otherwise 'array'
                        $keys[$path] = $objectClass ?? 'array';
                        // Recursively extract nested keys directly into $keys array
                        foreach ($this->extractKeysFlat($value, $path) as $nestedKey => $nestedType) {
                            $keys[$nestedKey] = $nestedType;
                        }
                    } else {
                        // Leaf value - get type from reflection if possible
                        $keys[$path] = $this->getTypeString($value);
                    }
                }
            }
        } elseif (is_object($data)) {
            // Convert object to array and process
            $arrayData = $this->objectToArrayPreservingObjects($data);
            foreach ($this->extractKeysFlat($arrayData, $prefix) as $nestedKey => $nestedType) {
                $keys[$nestedKey] = $nestedType;
            }
        }

        return $keys;
    }

    /**
     * Extract keys as multidimensional array.
     * Uses wildcards for array elements and union types for mixed values.
     *
     * @return array<int|string, mixed>
     */
    private function extractKeysMultidimensional(mixed $data): array
    {
        $keys = [];

        if (is_array($data)) {
            // Check if this is a numeric array (list)
            $isNumericArray = array_is_list($data);

            if ($isNumericArray && [] !== $data) {
                // Analyze all elements to find common structure
                $mergedStructure = $this->analyzeArrayElementsMultidimensional($data);
                $keys['*'] = $mergedStructure;
            } else {
                // Associative array - process each key normally
                foreach ($data as $key => $value) {
                    // Check if value is an object before converting
                    $isObject = is_object($value);
                    $objectClass = $isObject ? '\\' . $value::class : null;

                    // Convert objects to arrays for recursive processing, but preserve nested objects
                    if ($isObject) {
                        $value = $this->objectToArrayPreservingObjects($value);
                    }

                    if (is_array($value) && [] !== $value) {
                        // If it was an object, use class name, otherwise recurse
                        if ($objectClass) {
                            $keys[$key] = $objectClass;
                        } else {
                            $keys[$key] = $this->extractKeysMultidimensional($value);
                        }
                    } else {
                        // Leaf value - get type
                        $keys[$key] = $this->getTypeString($value);
                    }
                }
            }
        } elseif (is_object($data)) {
            // Convert object to array and process
            $arrayData = $this->objectToArrayPreservingObjects($data);
            $keys = $this->extractKeysMultidimensional($arrayData);
        }

        return $keys;
    }

    /**
     * Analyze all elements in a numeric array for multidimensional output.
     *
     * @param array<int, mixed> $elements
     * @return array<int|string, mixed>|string
     */
    private function analyzeArrayElementsMultidimensional(array $elements): array|string
    {
        if ([] === $elements) {
            return [];
        }

        $structures = [];

        // Collect structure from each element
        foreach ($elements as $element) {
            $structure = $this->extractElementStructureMultidimensional($element);
            $structures[] = $structure;
        }

        // Merge all structures
        return $this->mergeStructuresMultidimensional($structures);
    }

    /**
     * Extract structure from a single array element for multidimensional output.
     *
     * @return array<int|string, mixed>|string
     */
    private function extractElementStructureMultidimensional(mixed $element): array|string
    {
        // Check if element is an object
        if (is_object($element)) {
            // For objects, just return the class name
            return '\\' . $element::class;
        }

        if (is_array($element) && [] !== $element) {
            // Recursively extract nested structure
            return $this->extractKeysMultidimensional($element);
        }

        // Leaf value
        return $this->getTypeString($element);
    }

    /**
     * Merge multiple structures for multidimensional output.
     *
     * @param array<int, array<int|string, mixed>|string> $structures
     * @return array<int|string, mixed>|string
     */
    private function mergeStructuresMultidimensional(array $structures): array|string
    {
        if ([] === $structures) {
            return [];
        }

        // Check if all structures are strings (simple types)
        $allStrings = true;
        foreach ($structures as $structure) {
            if (!is_string($structure)) {
                $allStrings = false;
                break;
            }
        }

        if ($allStrings) {
            // All are simple types - create union
            /** @var array<int, string> $stringStructures */
            $stringStructures = $structures;
            $types = array_unique($stringStructures);
            sort($types);
            return implode('|', $types);
        }

        // At least one is an array - merge recursively
        $merged = [];

        // Collect all values for each key in a single pass
        $keyValues = [];
        foreach ($structures as $structure) {
            if (is_array($structure)) {
                foreach ($structure as $key => $value) {
                    if (is_array($value) || is_string($value)) {
                        $keyValues[$key][] = $value;
                    }
                }
            }
        }

        // Merge values for each key
        foreach ($keyValues as $key => $values) {
            /** @var array<int, array<int|string, mixed>|string> $values */
            $merged[$key] = $this->mergeStructuresMultidimensional($values);
        }

        return $merged;
    }

    /**
     * Analyze all elements in a numeric array to find common structure and union types.
     * Returns a merged structure with union types where values differ.
     *
     * @param array<int, mixed> $elements
     * @return array<string, string>
     */
    private function analyzeArrayElements(array $elements): array
    {
        if ([] === $elements) {
            return [];
        }

        $structures = [];

        // Collect structure from each element
        foreach ($elements as $element) {
            $structure = $this->extractElementStructure($element);
            $structures[] = $structure;
        }

        // Merge all structures with union types
        return $this->mergeStructures($structures);
    }

    /**
     * Extract structure from a single array element.
     *
     * @return array<string, string>
     */
    private function extractElementStructure(mixed $element): array
    {
        $structure = [];

        // Check if element is an object
        $isObject = is_object($element);

        if ($isObject) {
            // Store the object type at root level
            $structure[''] = '\\' . $element::class;

            // Extract properties from object using reflection for better type info
            $element = $this->objectToArrayPreservingObjects($element);
        }

        if (is_array($element) && [] !== $element) {
            if (!$isObject) {
                $structure[''] = 'array';
            }

            // Check if this is a numeric array
            $isNumericArray = array_is_list($element);

            if ($isNumericArray) {
                // Analyze all elements with wildcard
                $mergedStructure = $this->analyzeArrayElements($element);

                foreach ($mergedStructure as $subKey => $type) {
                    if ('' === $subKey) {
                        $structure['*'] = $type;
                    } else {
                        $structure['*.' . $subKey] = $type;
                    }
                }
            } else {
                // Associative array - process each key
                foreach ($element as $key => $value) {
                    $nestedStructure = $this->extractElementStructure($value);

                    foreach ($nestedStructure as $subKey => $type) {
                        $fullKey = '' === $subKey ? (string)$key : $key . '.' . $subKey;
                        $structure[$fullKey] = $type;
                    }
                }
            }
        } elseif (!$isObject) {
            // Leaf value
            $structure[''] = $this->getTypeString($element);
        }

        return $structure;
    }

    /**
     * Merge multiple structures into one with union types.
     * Optimized to use a single pass through all structures.
     *
     * @param array<int, array<string, string>> $structures
     * @return array<string, string>
     */
    private function mergeStructures(array $structures): array
    {
        if ([] === $structures) {
            return [];
        }

        /** @var array<string, array<string, bool>> $typesByKey */
        $typesByKey = [];

        // Collect all types for each key in a single pass
        foreach ($structures as $structure) {
            foreach ($structure as $key => $type) {
                if (!isset($typesByKey[$key])) {
                    $typesByKey[$key] = [$type => true];
                } else {
                    $typesByKey[$key][$type] = true;
                }
            }
        }

        // Convert to union types
        $merged = [];
        foreach ($typesByKey as $key => $types) {
            $typeList = array_keys($types);
            sort($typeList);
            $merged[$key] = implode('|', $typeList);
        }

        return $merged;
    }

    /**
     * Convert object to array while preserving nested objects.
     * This is used for key extraction to maintain object type information.
     *
     * @return array<int|string, mixed>
     */
    private function objectToArrayPreservingObjects(object $obj): array
    {
        // For stdClass and other objects, use get_object_vars to preserve nested objects
        $vars = get_object_vars($obj);
        if ([] !== $vars) {
            return $vars;
        }

        if (method_exists($obj, '__toString')) {
            return [(string)$obj];
        }

        return [];
    }

    /** Get type string for a value. */
    private function getTypeString(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return '\\' . $value::class;
        }

        return 'unknown';
    }

    /**
     * Check if a path exists (alias for exists()).
     *
     * @param string $path Dot-notation path
     * @return bool True if path exists, false otherwise
     */
    public function has(string $path): bool
    {
        return $this->exists($path);
    }

    /**
     * Check if any of the given paths exist.
     *
     * @param array<int, string> $paths Array of dot-notation paths
     * @return bool True if at least one path exists, false otherwise
     */
    public function hasAny(array $paths): bool
    {
        foreach ($paths as $path) {
            if ($this->exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all of the given paths exist.
     *
     * @param array<int, string> $paths Array of dot-notation paths
     * @return bool True if all paths exist, false otherwise
     */
    public function hasAll(array $paths): bool
    {
        foreach ($paths as $path) {
            if (!$this->exists($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get values matching a wildcard pattern.
     *
     * @param string $pattern Wildcard pattern (e.g., 'users.*.name')
     * @return array<int|string, mixed> Array of matching values
     */
    public function getWildcard(string $pattern): array
    {
        return $this->get($pattern, []);
    }

    /**
     * Get all keys from the root level.
     *
     * @return array<int, int|string> Array of keys
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Get all values from the root level.
     *
     * @return array<int, mixed> Array of values
     */
    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * Return the normalized internal array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Fast object to array conversion without json encode/decode.
     *
     * @return array<int|string, mixed>
     */
    private static function objectToArrayFast(object $obj): array
    {
        if ($obj instanceof stdClass) {
            return self::mixedToArray((array)$obj);
        }

        $vars = get_object_vars($obj);
        if ([] !== $vars) {
            return self::mixedToArray($vars);
        }

        if (method_exists($obj, '__toString')) {
            return [(string)$obj];
        }

        return [];
    }

    /**
     * Recursively normalize arrays by converting nested objects and stdClass to arrays.
     *
     * @param array<int|string, mixed> $arr
     * @return array<int|string, mixed>
     */
    private static function mixedToArray(array $arr): array
    {
        foreach ($arr as $k => $v) {
            if ($v instanceof stdClass) {
                $arr[$k] = self::mixedToArray((array)$v);
            } elseif (is_object($v)) {
                $arr[$k] = self::objectToArrayFast($v);
            } elseif (is_array($v)) {
                $arr[$k] = self::mixedToArray($v);
            }
        }

        return $arr;
    }

    /**
     * Convert SimpleXMLElement to array without json encode.
     *
     * @return array<int|string, mixed>
     */
    private static function xmlToArray(SimpleXMLElement $xml): array|string
    {
        $arr = [];
        foreach ($xml->attributes() as $name => $value) {
            $arr['@attributes'][(string)$name] = (string)$value;
        }

        foreach ($xml->children() as $name => $child) {
            $childVal = self::xmlToArray($child);
            if (array_key_exists($name, $arr)) {
                if (!is_array($arr[$name]) || !array_is_list($arr[$name])) {
                    $arr[$name] = [$arr[$name]];
                }
                $arr[$name][] = $childVal;
            } else {
                $arr[$name] = $childVal;
            }
        }

        $text = trim((string)$xml);
        if ('' !== $text) {
            if ([] !== $arr) {
                // element has both children/attributes and text
                $arr['@value'] = $text;
            } else {
                // pure text node → return scalar for convenience
                return $text;
            }
        }

        return $arr;
    }
}
