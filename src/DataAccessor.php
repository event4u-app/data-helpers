<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

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
            ? $this->extract($this->data, $pathInfo['segments'])
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
     * Static accessor: Get value from data using dot-notation path.
     *
     * This is a convenience method that creates a temporary DataAccessor instance.
     * The path is cached statically for performance.
     *
     * Example:
     *   DataAccessor::getValue($data, 'user.name')
     *   DataAccessor::getValue($data, 'users.*.email')
     *
     * @param mixed $data Source data (array, object, Collection, etc.)
     * @param string $path Dot-notation path
     * @param mixed $default Default if path not found
     */
    public static function getValue(mixed $data, string $path, mixed $default = null): mixed
    {
        $accessor = new self($data);

        return $accessor->get($path, $default);
    }

    /**
     * Static accessor: Get value as string.
     *
     * @param mixed $data Source data
     * @param string $path Dot-notation path
     * @param null|string $default Default if path not found
     */
    public static function getStringValue(mixed $data, string $path, ?string $default = null): ?string
    {
        $accessor = new self($data);

        return $accessor->getString($path, $default);
    }

    /**
     * Static accessor: Get value as integer.
     *
     * @param mixed $data Source data
     * @param string $path Dot-notation path
     * @param null|int $default Default if path not found
     */
    public static function getIntValue(mixed $data, string $path, ?int $default = null): ?int
    {
        $accessor = new self($data);

        return $accessor->getInt($path, $default);
    }

    /**
     * Static accessor: Get value as float.
     *
     * @param mixed $data Source data
     * @param string $path Dot-notation path
     * @param null|float $default Default if path not found
     */
    public static function getFloatValue(mixed $data, string $path, ?float $default = null): ?float
    {
        $accessor = new self($data);

        return $accessor->getFloat($path, $default);
    }

    /**
     * Static accessor: Get value as boolean.
     *
     * @param mixed $data Source data
     * @param string $path Dot-notation path
     * @param null|bool $default Default if path not found
     */
    public static function getBoolValue(mixed $data, string $path, ?bool $default = null): ?bool
    {
        $accessor = new self($data);

        return $accessor->getBool($path, $default);
    }

    /**
     * Static accessor: Get value as array.
     *
     * @param mixed $data Source data
     * @param string $path Dot-notation path
     * @param null|array<int|string, mixed> $default Default if path not found
     * @return null|array<int|string, mixed>
     */
    public static function getArrayValue(mixed $data, string $path, ?array $default = null): ?array
    {
        $accessor = new self($data);

        return $accessor->getArray($path, $default);
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
     * @param int $index Current position in segments array (for performance)
     * @return null|array<int|string, mixed>
     */
    private function extract(mixed $current, array $segments, string $prefix = '', int $index = 0): ?array
    {
        // Base case: reached end of path
        if (count($segments) <= $index) {
            return [
                $prefix => $current,
            ];
        }

        // Get current segment using O(1) index access instead of O(n) array_shift
        $segment = $segments[$index];

        // Wildcard
        if (DotPathHelper::isWildcard($segment)) {
            if (CollectionHelper::isCollection($current)) {
                $current = CollectionHelper::toArray($current);
            } elseif (EntityHelper::isEntity($current)) {
                $current = EntityHelper::getAttributes($current);
            }

            if (!is_array($current)) {
                return null;
            }

            return $this->collectFromIterable($current, $segments, $prefix, $index + 1);
        }

        // Traverse array
        if (is_array($current) && array_key_exists($segment, $current)) {
            $newPrefix = DotPathHelper::buildPrefix($prefix, $segment);

            return $this->extract($current[$segment], $segments, $newPrefix, $index + 1);
        }

        // Traverse entity/model
        if (EntityHelper::hasAttribute($current, $segment)) {
            $value = EntityHelper::getAttribute($current, $segment);
            $newPrefix = DotPathHelper::buildPrefix($prefix, $segment);

            return $this->extract($value, $segments, $newPrefix, $index + 1);
        }

        // Traverse collection
        if (CollectionHelper::has($current, $segment)) {
            $value = CollectionHelper::get($current, $segment);
            $newPrefix = DotPathHelper::buildPrefix($prefix, $segment);

            return $this->extract($value, $segments, $newPrefix, $index + 1);
        }

        return null;
    }

    /**
     * Merge results from iterating wildcard children.
     *
     * @param array<int|string, mixed> $current
     * @param array<int, string> $segments Full segments array
     * @param int $index Current position in segments (after wildcard)
     * @return array<int|string, mixed>
     */
    private function collectFromIterable(array $current, array $segments, string $prefix, int $index): array
    {
        $collected = [];
        foreach ($current as $key => $item) {
            // Inline prefix building for performance (avoid function call overhead)
            $newPrefix = '' === $prefix ? (string)$key : $prefix . '.' . $key;
            $value = $this->extract($item, $segments, $newPrefix, $index);
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $collected[$k] = $v; // avoid array_merge copies
                }
            }
        }

        return $collected;
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
