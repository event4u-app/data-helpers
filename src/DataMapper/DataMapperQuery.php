<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use Closure;
use event4u\DataHelpers\DataMapper;

/**
 * Fluent query builder for DataMapper with Laravel-style syntax.
 *
 * Provides a chainable API for building complex data mapping queries with filters,
 * sorting, grouping, and aggregations.
 *
 * @example
 * ```php
 * $result = DataMapperQuery::source('products', $products)
 *     ->where('category', 'Electronics')
 *     ->where('price', '>', 100)
 *     ->orderBy('price', 'DESC')
 *     ->limit(10)
 *     ->get();
 * ```
 */
class DataMapperQuery
{
    /** @var array<string, mixed> Named sources */
    private array $sources = [];

    /** @var array<string, mixed>|null Template structure */
    private ?array $template = null;

    /** @var array<string, mixed> WHERE conditions */
    private array $whereConditions = [];

    /** @var array<string, string> ORDER BY clauses */
    private array $orderByFields = [];

    /** @var int|null LIMIT value */
    private ?int $limitValue = null;

    /** @var int|null OFFSET value */
    private ?int $offsetValue = null;

    /** @var array<string, mixed>|null GROUP BY configuration */
    private ?array $groupByConfig = null;

    /** @var array<string, mixed> HAVING conditions */
    private array $havingConditions = [];

    /** @var string|null DISTINCT field */
    private ?string $distinctField = null;

    /** @var array<string, mixed> LIKE patterns */
    private array $likePatterns = [];

    /** @var bool Skip null values */
    private bool $skipNull = true;

    /** @var bool Reindex wildcard results */
    private bool $reindexWildcard = false;

    /** @var string|null Primary source key for wildcard path */
    private ?string $primarySource = null;

    /** @var array<int, string> Order in which operators were called */
    private array $operatorOrder = [];

    /** Create a new query builder instance (static factory). */
    public static function query(): self
    {
        return new self();
    }

    /**
     * Add a named source.
     *
     * @param string $name Source name
     * @param mixed $data Source data
     */
    public function source(string $name, mixed $data): self
    {
        $this->sources[$name] = $data;

        if (null === $this->primarySource) {
            $this->primarySource = $name;
        }

        return $this;
    }

    /**
     * Set the template structure.
     *
     * @param array<string, mixed> $template Template array
     */
    public function template(array $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Add a WHERE condition.
     *
     * Supports multiple forms:
     * - where('field', 'value') - Equality comparison
     * - where('field', '=', 'value') - Explicit equality
     * - where('field', '>', 100) - Greater than
     * - where('field', '<', 100) - Less than
     * - where('field', '>=', 100) - Greater than or equal
     * - where('field', '<=', 100) - Less than or equal
     * - where('field', '!=', 'value') - Not equal
     * - where('field', '<>', 'value') - Not equal (alternative)
     * - where(Closure $callback) - Nested conditions
     *
     * @param string|Closure(self): void $field Field path or closure for nested conditions
     * @param mixed $operator Operator or value (if operator is omitted)
     * @param mixed $value Value (optional if operator is omitted)
     */
    public function where(string|Closure $field, mixed $operator = null, mixed $value = null): self
    {
        // Track operator order (only on first WHERE call)
        if ([] === $this->whereConditions) {
            $this->operatorOrder[] = 'WHERE';
        }

        // Handle closure for nested conditions
        if ($field instanceof Closure) {
            $nestedQuery = new self();
            $field($nestedQuery);

            // If we already have conditions, wrap them in AND
            if ([] !== $this->whereConditions) {
                $this->whereConditions = ['AND' => [$this->whereConditions, $nestedQuery->whereConditions]];
            } else {
                $this->whereConditions = $nestedQuery->whereConditions;
            }

            return $this;
        }

        // Handle two-argument form: where('field', 'value')
        if (null === $value) {
            $value = $operator;
            $operator = '=';
        }

        // If we already have a condition for this field, wrap in AND
        if (isset($this->whereConditions[$field])) {
            // Convert to AND structure
            $existingCondition = [$field => $this->whereConditions[$field]];
            unset($this->whereConditions[$field]);

            $newCondition = [$field => [$operator, $value]];

            if (isset($this->whereConditions['AND']) && is_array($this->whereConditions['AND'])) {
                // Already have AND, add to it
                $this->whereConditions['AND'][] = $newCondition;
            } else {
                // Create new AND
                $this->whereConditions = ['AND' => [$existingCondition, $newCondition]];
            }
        } else {
            // Store as array with operator and value
            $this->whereConditions[$field] = [$operator, $value];
        }

        return $this;
    }

    /**
     * Add an OR WHERE condition.
     *
     * @param string|Closure(self): void $field Field path or closure for nested conditions
     * @param mixed $operator Operator or value (if operator is omitted)
     * @param mixed $value Value (optional if operator is omitted)
     */
    public function orWhere(string|Closure $field, mixed $operator = null, mixed $value = null): self
    {
        // If we have existing conditions that are not in OR structure, convert them
        if ([] !== $this->whereConditions && !isset($this->whereConditions['OR'])) {
            $existingConditions = $this->whereConditions;
            $this->whereConditions = [
                'OR' => [$existingConditions],
            ];
        }

        // Handle closure for nested conditions
        if ($field instanceof Closure) {
            $nestedQuery = new self();
            $field($nestedQuery);

            if (!isset($this->whereConditions['OR']) || !is_array($this->whereConditions['OR'])) {
                $this->whereConditions['OR'] = [];
            }

            $this->whereConditions['OR'][] = $nestedQuery->whereConditions;

            return $this;
        }

        // Handle two-argument form: orWhere('field', 'value')
        if (null === $value) {
            $value = $operator;
            $operator = '=';
        }

        if (!isset($this->whereConditions['OR']) || !is_array($this->whereConditions['OR'])) {
            $this->whereConditions['OR'] = [];
        }

        // Store as array with operator and value
        $this->whereConditions['OR'][] = [$field => [$operator, $value]];

        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $field Field path
     * @param string $direction Direction (ASC or DESC)
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        // Track operator order (only on first ORDER BY call)
        if ([] === $this->orderByFields) {
            $this->operatorOrder[] = 'ORDER BY';
        }

        $this->orderByFields[$field] = strtoupper($direction);

        return $this;
    }

    /**
     * Set LIMIT.
     *
     * @param int $limit Number of items to return
     */
    public function limit(int $limit): self
    {
        // Track operator order
        if (null === $this->limitValue) {
            $this->operatorOrder[] = 'LIMIT';
        }

        $this->limitValue = $limit;

        return $this;
    }

    /**
     * Set OFFSET.
     *
     * @param int $offset Number of items to skip
     */
    public function offset(int $offset): self
    {
        // Track operator order
        if (null === $this->offsetValue) {
            $this->operatorOrder[] = 'OFFSET';
        }

        $this->offsetValue = $offset;

        return $this;
    }

    /**
     * Set GROUP BY with aggregations.
     *
     * @param string|array<int, string> $fields Field(s) to group by
     * @param array<string, array<int, mixed>>|null $aggregations Aggregation functions
     */
    public function groupBy(string|array $fields, ?array $aggregations = null): self
    {
        // Track operator order
        if (null === $this->groupByConfig) {
            $this->operatorOrder[] = 'GROUP BY';
        }

        $this->groupByConfig = [
            'field' => $fields,
        ];

        if (null !== $aggregations) {
            $this->groupByConfig['aggregations'] = $aggregations;
        }

        return $this;
    }

    /**
     * Add aggregation to GROUP BY.
     *
     * @param string $name Aggregation name
     * @param string $function Aggregation function (COUNT, SUM, AVG, etc.)
     * @param string|null $field Field to aggregate (optional for COUNT)
     */
    public function aggregate(string $name, string $function, ?string $field = null): self
    {
        if (null === $this->groupByConfig) {
            $this->groupByConfig = [];
        }

        if (!isset($this->groupByConfig['aggregations'])) {
            $this->groupByConfig['aggregations'] = [];
        }

        $aggregation = [$function];
        if (null !== $field) {
            $aggregation[] = $field;
        }

        if (!is_array($this->groupByConfig)) {
            $this->groupByConfig = [];
        }
        if (!isset($this->groupByConfig['aggregations']) || !is_array($this->groupByConfig['aggregations'])) {
            $this->groupByConfig['aggregations'] = [];
        }

        $this->groupByConfig['aggregations'][$name] = $aggregation;

        return $this;
    }

    /**
     * Add a HAVING condition (for GROUP BY).
     *
     * @param string $field Aggregation field name
     * @param string $operator Comparison operator (=, !=, >, <, >=, <=)
     * @param mixed $value Value to compare against
     */
    public function having(string $field, string $operator, mixed $value): self
    {
        $this->havingConditions[$field] = [$operator, $value];

        return $this;
    }

    /**
     * Set DISTINCT field.
     *
     * @param string $field Field to make distinct
     */
    public function distinct(string $field): self
    {
        // Track operator order
        if (null === $this->distinctField) {
            $this->operatorOrder[] = 'DISTINCT';
        }

        $this->distinctField = $field;

        return $this;
    }

    /**
     * Add a LIKE pattern.
     *
     * @param string $field Field path
     * @param string $pattern Pattern with % wildcards
     */
    public function like(string $field, string $pattern): self
    {
        // Track operator order (only on first LIKE call)
        if ([] === $this->likePatterns) {
            $this->operatorOrder[] = 'LIKE';
        }

        $this->likePatterns[$field] = $pattern;

        return $this;
    }

    /**
     * Set skip null option.
     *
     * @param bool $skip Whether to skip null values
     */
    public function skipNull(bool $skip = true): self
    {
        $this->skipNull = $skip;

        return $this;
    }

    /**
     * Set reindex wildcard option.
     *
     * @param bool $reindex Whether to reindex wildcard results
     */
    public function reindex(bool $reindex = true): self
    {
        $this->reindexWildcard = $reindex;

        return $this;
    }

    /**
     * Execute the query and return results.
     *
     * @return array<int|string, mixed>
     */
    public function get(): array
    {
        $template = $this->buildTemplate();
        $result = DataMapper::mapFromTemplate($template, $this->sources, $this->skipNull, $this->reindexWildcard);

        // If we have a primary source, return the wildcard data from it
        if (null !== $this->primarySource && isset($result[$this->primarySource])) {
            $sourceData = $result[$this->primarySource];

            // If the result has a '*' key (wildcard mapping), return that
            if (is_array($sourceData) && isset($sourceData['*']) && is_array($sourceData['*'])) {
                return $sourceData['*'];
            }

            if (is_array($sourceData)) {
                return $sourceData;
            }
        }

        return $result;
    }

    /**
     * Build the template array from query configuration.
     *
     * @return array<string, mixed>
     */
    private function buildTemplate(): array
    {
        // If template is explicitly set, use it
        if (null !== $this->template) {
            return $this->template;
        }

        // Build template automatically from primary source
        if (null === $this->primarySource) {
            return [];
        }

        $sourceKey = $this->primarySource;
        $wildcardMapping = [];

        // Build operators in the order they were called
        foreach ($this->operatorOrder as $operatorName) {
            switch ($operatorName) {
                case 'WHERE':
                    if ([] !== $this->whereConditions) {
                        $wildcardMapping['WHERE'] = $this->buildWhereConditions($this->whereConditions, $sourceKey);
                    }
                    break;

                case 'DISTINCT':
                    if (null !== $this->distinctField) {
                        $wildcardMapping['DISTINCT'] = $this->wrapFieldPath($this->distinctField, $sourceKey);
                    }
                    break;

                case 'LIKE':
                    if ([] !== $this->likePatterns) {
                        $likeConfig = [];
                        foreach ($this->likePatterns as $field => $pattern) {
                            $likeConfig[$this->wrapFieldPath($field, $sourceKey)] = $pattern;
                        }
                        $wildcardMapping['LIKE'] = $likeConfig;
                    }
                    break;

                case 'GROUP BY':
                    if (null !== $this->groupByConfig) {
                        $groupByConfig = $this->groupByConfig;

                        // Wrap field paths
                        if (isset($groupByConfig['field'])) {
                            if (is_array($groupByConfig['field'])) {
                                /** @var array<int|string, mixed> $fields */
                                $fields = $groupByConfig['field'];
                                $groupByConfig['field'] = array_map(
                                    fn(mixed $f): string => $this->wrapFieldPath((string)$f, $sourceKey),
                                    $fields
                                );
                            } elseif (is_string($groupByConfig['field'])) {
                                $groupByConfig['field'] = $this->wrapFieldPath($groupByConfig['field'], $sourceKey);
                            }
                        }

                        // Wrap aggregation field paths
                        if (isset($groupByConfig['aggregations']) && is_array($groupByConfig['aggregations'])) {
                            /** @var array<string, array<int, mixed>> $aggregations */
                            $aggregations = $groupByConfig['aggregations'];
                            foreach ($aggregations as $name => $agg) {
                                if (is_array($agg) && isset($agg[1]) && is_string($agg[1])) {
                                    $aggregations[$name][1] = $this->wrapFieldPath($agg[1], $sourceKey);
                                }
                            }
                            $groupByConfig['aggregations'] = $aggregations;
                        }

                        // Add HAVING conditions
                        if ([] !== $this->havingConditions) {
                            $groupByConfig['HAVING'] = $this->havingConditions;
                        }

                        $wildcardMapping['GROUP BY'] = $groupByConfig;
                    }
                    break;

                case 'ORDER BY':
                    if ([] !== $this->orderByFields) {
                        $orderByConfig = [];
                        foreach ($this->orderByFields as $field => $direction) {
                            $orderByConfig[$this->wrapFieldPath($field, $sourceKey)] = $direction;
                        }
                        $wildcardMapping['ORDER BY'] = $orderByConfig;
                    }
                    break;

                case 'OFFSET':
                    if (null !== $this->offsetValue) {
                        $wildcardMapping['OFFSET'] = $this->offsetValue;
                    }
                    break;

                case 'LIMIT':
                    if (null !== $this->limitValue) {
                        $wildcardMapping['LIMIT'] = $this->limitValue;
                    }
                    break;
            }
        }

        // Add wildcard template (return all fields)
        $wildcardMapping['*'] = '{{ ' . $sourceKey . '.* }}';

        return [
            $sourceKey => $wildcardMapping,
        ];
    }

    /**
     * Build WHERE conditions recursively.
     *
     * @param array<string, mixed> $conditions Conditions array
     * @param string $sourceKey Source key for field paths
     * @return array<string, mixed>
     */
    private function buildWhereConditions(array $conditions, string $sourceKey): array
    {
        $result = [];

        foreach ($conditions as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            // Handle AND/OR nested conditions
            if ('AND' === $key || 'OR' === $key) {
                if (is_array($value)) {
                    // Check if it's a single nested condition or array of conditions
                    if (isset($value[0]) && is_array($value[0])) {
                        // Array of conditions
                        $result[$key] = array_map(
                            fn(array $cond): array => $this->buildWhereConditions($cond, $sourceKey),
                            $value
                        );
                    } else {
                        // Single nested condition
                        $result[$key] = $this->buildWhereConditions($value, $sourceKey);
                    }
                }
            } else {
                // Regular field condition
                $result[$this->wrapFieldPath($key, $sourceKey)] = $value;
            }
        }

        return $result;
    }

    /**
     * Wrap field path in template expression.
     *
     * @param string $field Field path
     * @param string $sourceKey Source key
     * @return string Wrapped field path
     */
    private function wrapFieldPath(string $field, string $sourceKey): string
    {
        // If already wrapped, return as-is
        if (str_starts_with($field, '{{') && str_ends_with($field, '}}')) {
            return $field;
        }

        // If field already contains source key, just wrap it
        if (str_starts_with($field, $sourceKey . '.')) {
            return '{{ ' . $field . ' }}';
        }

        // Add source key and wildcard
        return '{{ ' . $sourceKey . '.*.' . $field . ' }}';
    }
}