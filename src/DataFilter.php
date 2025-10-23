<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\OperatorRegistry;
use Illuminate\Support\Collection;
use SimpleXMLElement;

/**
 * DataFilter - Filter and query data collections.
 *
 * Provides a fluent interface for filtering arrays, DTOs, Models, Collections, JSON, and XML.
 * Works on already-loaded data (post-mapping filtering).
 *
 * Example:
 *   $filtered = DataFilter::query($products)
 *       ->where('price', '>', 100)
 *       ->orderBy('price', 'DESC')
 *       ->limit(10)
 *       ->get();
 */
final class DataFilter
{
    /** @var array<int, array{operator: string, config: mixed}> */
    private array $operators = [];

    private string $outputFormat = 'array';

    private ?string $originalFormat = null;

    /** @param array<int|string, mixed> $items Items to filter */
    private function __construct(private array $items)
    {
    }

    /**
     * Create a new DataFilter query.
     *
     * @param mixed $data Data to filter (array, Collection, DTO[], Model[], JSON string, XML string)
     */
    public static function query(mixed $data): self
    {
        $instance = new self([]);
        $instance->setData($data);

        return $instance;
    }

    /**
     * Set the data to filter.
     *
     * @param mixed $data Data to filter
     */
    private function setData(mixed $data): void
    {
        // Handle Collection
        if ($data instanceof Collection) {
            $this->items = $data->all();
            $this->originalFormat = 'collection';
            return;
        }

        // Handle JSON string
        if (is_string($data) && $this->isJson($data)) {
            $decoded = json_decode($data, true);
            $this->items = is_array($decoded) ? $decoded : [];
            $this->originalFormat = 'json';
            return;
        }

        // Handle XML string
        if (is_string($data) && $this->isXml($data)) {
            $this->items = $this->xmlToArray($data);
            $this->originalFormat = 'xml';
            return;
        }

        // Handle array (including DTOs, Models)
        if (is_array($data)) {
            $this->items = $data;
            $this->originalFormat = 'array';
            return;
        }

        // Fallback
        $this->items = [];
        $this->originalFormat = 'array';
    }

    /**
     * Add a WHERE condition.
     *
     * @param string $field Field name
     * @param mixed $operator Operator or value (if 2 args)
     * @param mixed $value Value (if 3 args)
     */
    public function where(string $field, mixed $operator, mixed $value = null): self
    {
        // Handle 2-argument form: where('field', 'value')
        if (null === $value) {
            $value = $operator;
            $operator = '=';
        }

        $this->operators[] = [
            'operator' => 'WHERE',
            'config' => [$field => [$operator, $value]],
        ];

        return $this;
    }

    /**
     * Add an OR WHERE condition.
     *
     * @param string $field Field name
     * @param mixed $operator Operator or value (if 2 args)
     * @param mixed $value Value (if 3 args)
     */
    public function orWhere(string $field, mixed $operator, mixed $value = null): self
    {
        // Handle 2-argument form
        if (null === $value) {
            $value = $operator;
            $operator = '=';
        }

        $this->operators[] = [
            'operator' => 'WHERE',
            'config' => ['OR' => [$field => [$operator, $value]]],
        ];

        return $this;
    }

    /**
     * Add a BETWEEN condition.
     *
     * @param string $field Field name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     */
    public function between(string $field, mixed $min, mixed $max): self
    {
        $this->operators[] = [
            'operator' => 'BETWEEN',
            'config' => [$field => [$min, $max]],
        ];

        return $this;
    }

    /**
     * Add a NOT BETWEEN condition.
     *
     * @param string $field Field name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     */
    public function notBetween(string $field, mixed $min, mixed $max): self
    {
        $this->operators[] = [
            'operator' => 'NOT BETWEEN',
            'config' => [$field => [$min, $max]],
        ];

        return $this;
    }

    /**
     * Add a WHERE IN condition.
     *
     * @param string $field Field name
     * @param array<int, mixed> $values Values
     */
    public function whereIn(string $field, array $values): self
    {
        $this->operators[] = [
            'operator' => 'WHERE IN',
            'config' => [$field => $values],
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT IN condition.
     *
     * @param string $field Field name
     * @param array<int, mixed> $values Values
     */
    public function whereNotIn(string $field, array $values): self
    {
        $this->operators[] = [
            'operator' => 'WHERE NOT IN',
            'config' => [$field => $values],
        ];

        return $this;
    }

    /**
     * Add a WHERE NULL condition.
     *
     * @param string $field Field name
     */
    public function whereNull(string $field): self
    {
        $this->operators[] = [
            'operator' => 'WHERE NULL',
            'config' => [$field => true],
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT NULL condition.
     *
     * @param string $field Field name
     */
    public function whereNotNull(string $field): self
    {
        $this->operators[] = [
            'operator' => 'WHERE NOT NULL',
            'config' => [$field => true],
        ];

        return $this;
    }

    /**
     * Add an EXISTS condition (alias for whereNotNull).
     *
     * @param string $field Field name
     */
    public function exists(string $field): self
    {
        return $this->whereNotNull($field);
    }

    /**
     * Add a NOT EXISTS condition (alias for whereNull).
     *
     * @param string $field Field name
     */
    public function notExists(string $field): self
    {
        return $this->whereNull($field);
    }

    /**
     * Add a LIKE condition.
     *
     * @param string $field Field name
     * @param string $pattern Pattern with % wildcards
     */
    public function like(string $field, string $pattern): self
    {
        $this->operators[] = [
            'operator' => 'LIKE',
            'config' => [$field => $pattern],
        ];

        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $field Field name
     * @param string $direction Direction (ASC or DESC)
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->operators[] = [
            'operator' => 'ORDER BY',
            'config' => [$field => strtoupper($direction)],
        ];

        return $this;
    }

    /**
     * Set LIMIT.
     *
     * @param int $limit Maximum number of results
     */
    public function limit(int $limit): self
    {
        $this->operators[] = [
            'operator' => 'LIMIT',
            'config' => $limit,
        ];

        return $this;
    }

    /**
     * Set OFFSET.
     *
     * @param int $offset Number of items to skip
     */
    public function offset(int $offset): self
    {
        $this->operators[] = [
            'operator' => 'OFFSET',
            'config' => $offset,
        ];

        return $this;
    }

    /**
     * Add a DISTINCT clause.
     *
     * @param string $field Field name to check for distinctness
     */
    public function distinct(string $field): self
    {
        $this->operators[] = [
            'operator' => 'DISTINCT',
            'config' => $field,
        ];

        return $this;
    }

    /**
     * Add a custom operator.
     *
     * @param string $operatorName Operator name
     * @param mixed $config Operator configuration
     */
    public function addOperator(string $operatorName, mixed $config): self
    {
        $this->operators[] = [
            'operator' => $operatorName,
            'config' => $config,
        ];

        return $this;
    }

    /**
     * Set output format.
     *
     * @param string $format Output format ('array', 'json', 'xml', 'collection', 'original')
     */
    public function format(string $format): self
    {
        $this->outputFormat = $format;

        return $this;
    }

    /**
     * Execute the query and return results.
     *
     * @return mixed Filtered results in requested format
     */
    public function get(): mixed
    {
        $result = $this->items;

        // Create context for direct mode (field names, not template paths)
        $context = OperatorContext::forDirect($result);

        // Apply operators in order
        foreach ($this->operators as $op) {
            $operator = OperatorRegistry::get($op['operator']);
            $result = $operator->apply($result, $op['config'], $context);
        }

        // Convert to requested format
        return $this->convertToFormat($result);
    }

    /**
     * Get the first result or null.
     *
     * @return mixed First result or null
     */
    public function first(): mixed
    {
        $result = $this->get();

        if (!is_array($result) || [] === $result) {
            return null;
        }

        return reset($result);
    }

    /** Count the number of results. */
    public function count(): int
    {
        $result = $this->get();

        if (!is_array($result)) {
            return 0;
        }

        return count($result);
    }

    /**
     * Convert result to requested format.
     *
     * @param array<int|string, mixed> $result Result array
     */
    private function convertToFormat(array $result): mixed
    {
        $format = 'original' === $this->outputFormat ? $this->originalFormat : $this->outputFormat;

        return match ($format) {
            'json' => json_encode($result),
            'xml' => $this->arrayToXml($result),
            'collection' => new Collection($result),
            default => $result,
        };
    }

    /** Check if string is valid JSON. */
    private function isJson(string $string): bool
    {
        json_decode($string);

        return JSON_ERROR_NONE === json_last_error();
    }

    /** Check if string is valid XML. */
    private function isXml(string $string): bool
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($string);
        libxml_use_internal_errors($prev);

        return false !== $doc;
    }

    /**
     * Convert XML string to array.
     *
     * @return array<int|string, mixed>
     */
    private function xmlToArray(string $xml): array
    {
        $obj = simplexml_load_string($xml);
        if (false === $obj) {
            return [];
        }

        $json = json_encode($obj);
        if (false === $json) {
            return [];
        }

        $array = json_decode($json, true);

        return is_array($array) ? $array : [];
    }

    /**
     * Convert array to XML string.
     *
     * @param array<int|string, mixed> $array Array to convert
     */
    private function arrayToXml(array $array): string
    {
        $xml = new SimpleXMLElement('<root/>');
        $this->arrayToXmlRecursive($array, $xml);

        $result = $xml->asXML();

        return false !== $result ? $result : '';
    }

    /**
     * Recursively convert array to XML.
     *
     * @param array<int|string, mixed> $array Array to convert
     * @param SimpleXMLElement $xml XML element
     */
    private function arrayToXmlRecursive(array $array, SimpleXMLElement $xml): void
    {
        foreach ($array as $key => $value) {
            $key = is_int($key) ? 'item' . $key : $key;

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
}
