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
        $segments = DotPathHelper::segments($path);

        // Lazy wildcard expansion: check if path contains wildcard
        $hasWildcard = DotPathHelper::containsWildcard($path);

        $results = $hasWildcard
            ? $this->extract($this->data, $segments)
            : $this->extractSimple($this->data, $segments);

        if (null === $results) {
            return $default;
        }

        if ($hasWildcard) {
            return $results; // always array with dot-paths
        }

        // Non-wildcard paths: unwrap single-element array
        if (is_array($results) && count($results) === 1) {
            return reset($results);
        }

        return $results;
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
     * @return null|array<int|string, mixed>
     */
    private function extract(mixed $current, array $segments, string $prefix = ''): ?array
    {
        if ([] === $segments) {
            return [
                $prefix => $current,
            ];
        }

        $segment = array_shift($segments);

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

            return $this->collectFromIterable($current, $segments, $prefix);
        }

        // Traverse array
        if (is_array($current) && array_key_exists($segment, $current)) {
            $newPrefix = DotPathHelper::buildPrefix($prefix, $segment);

            return $this->extract($current[$segment], $segments, $newPrefix);
        }

        // Traverse entity/model
        if (EntityHelper::hasAttribute($current, $segment)) {
            $value = EntityHelper::getAttribute($current, $segment);
            $newPrefix = DotPathHelper::buildPrefix($prefix, $segment);

            return $this->extract($value, $segments, $newPrefix);
        }

        // Traverse collection
        if (CollectionHelper::has($current, $segment)) {
            $value = CollectionHelper::get($current, $segment);
            $newPrefix = DotPathHelper::buildPrefix($prefix, $segment);

            return $this->extract($value, $segments, $newPrefix);
        }

        return null;
    }

    /**
     * Merge results from iterating wildcard children.
     *
     * @param array<int|string, mixed> $current
     * @param array<int, string> $remainingSegments
     * @return array<int|string, mixed>
     */
    private function collectFromIterable(array $current, array $remainingSegments, string $prefix): array
    {
        $collected = [];
        foreach ($current as $key => $item) {
            $newPrefix = DotPathHelper::buildPrefix($prefix, (string)$key);
            $value = $this->extract($item, $remainingSegments, $newPrefix);
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
