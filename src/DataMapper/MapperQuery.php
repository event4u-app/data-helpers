<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * MapperQuery - Query on wildcards during mapping.
 *
 * Allows filtering, sorting, and limiting wildcard results during mapping.
 *
 * Example:
 *   $mapper->query('project.positions.*')
 *       ->where('status', 'active')
 *       ->orderBy('salary', 'DESC')
 *       ->limit(10);
 */
final class MapperQuery
{
    /** @var array<int, array{field: string, operator: string, value: mixed}> */
    private array $whereConditions = [];

    /** @var array<int, array{field: string, direction: string}> */
    private array $orderByConditions = [];

    private ?int $limit = null;

    private ?int $offset = null;

    /** @var array<int, string> */
    private array $groupByFields = [];

    /**
     * Create a new MapperQuery instance.
     *
     * @param string $wildcardPath Wildcard path (e.g., 'project.positions.*')
     * @param FluentDataMapper $mapper Parent mapper
     */
    public function __construct(private readonly string $wildcardPath, private readonly FluentDataMapper $mapper)
    {
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
        // Handle 2-argument syntax: where('field', 'value')
        if (null === $value) {
            $value = $operator;
            $operator = '=';
        }

        $this->whereConditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add an ORDER BY condition.
     *
     * @param string $field Field name
     * @param string $direction Direction (ASC or DESC)
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderByConditions[] = [
            'field' => $field,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /** Set LIMIT. */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /** Set OFFSET. */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /** Add GROUP BY field. */
    public function groupBy(string $field): self
    {
        $this->groupByFields[] = $field;

        return $this;
    }

    /** Get the wildcard path. */
    public function getWildcardPath(): string
    {
        return $this->wildcardPath;
    }

    /**
     * Get WHERE conditions.
     *
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    public function getWhereConditions(): array
    {
        return $this->whereConditions;
    }

    /**
     * Get ORDER BY conditions.
     *
     * @return array<int, array{field: string, direction: string}>
     */
    public function getOrderByConditions(): array
    {
        return $this->orderByConditions;
    }

    /** Get LIMIT. */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /** Get OFFSET. */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Get GROUP BY fields.
     *
     * @return array<int, string>
     */
    public function getGroupByFields(): array
    {
        return $this->groupByFields;
    }

    /** Return to the parent mapper. */
    public function end(): FluentDataMapper
    {
        return $this->mapper;
    }

    /**
     * Clear conditions for a specific operator.
     *
     * @param string $operator Operator name (e.g., 'WHERE', 'ORDER BY')
     * @internal Used by FluentDataMapper::resetTemplateOperator()
     */
    public function clearConditionsForOperator(string $operator): void
    {
        match ($operator) {
            'WHERE' => $this->whereConditions = [],
            'ORDER BY' => $this->orderByConditions = [],
            'LIMIT' => $this->limit = null,
            'OFFSET' => $this->offset = null,
            'GROUP BY' => $this->groupByFields = [],
            default => null,
        };
    }
}
