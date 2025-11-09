<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

/**
 * DataFilterWrapper - Wraps DataFilter to return DataCollection.
 *
 * This wrapper allows using DataFilter with DataCollection while ensuring
 * that the result is always a DataCollection instance.
 *
 * Example:
 *   $collection = DataCollection::make($data);
 *   $filtered = $collection
 *       ->query()
 *       ->where('age', '>', 25)
 *       ->orderBy('name')
 *       ->get();  // Returns DataCollection
 *
 * @template TValue
 * @phpstan-ignore missingType.generics
 */
final readonly class DataFilterWrapper
{
    private DataFilter $filter;

    /** @param array<int|string, mixed> $items Items to filter */
    public function __construct(array $items)
    {
        $this->filter = DataFilter::make($items);
    }

    /**
     * Add a WHERE condition.
     *
     * @param string $field Field name
     * @param mixed $operator Operator or value (if 2 args)
     * @param mixed $value Value (if 3 args)
     */
    public function where(
        string $field,
        mixed $operator,
        mixed $value = null
    ): self // @phpstan-ignore missingType.generics
    {
        $this->filter->where($field, $operator, $value);
        return $this;
    }

    /**
     * Add an OR WHERE condition.
     *
     * @param string $field Field name
     * @param mixed $operator Operator or value (if 2 args)
     * @param mixed $value Value (if 3 args)
     */
    public function orWhere(
        string $field,
        mixed $operator,
        mixed $value = null
    ): self // @phpstan-ignore missingType.generics
    {
        $this->filter->orWhere($field, $operator, $value);
        return $this;
    }

    /**
     * Add a BETWEEN condition.
     *
     * @param string $field Field name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     */
    public function between(string $field, mixed $min, mixed $max): self // @phpstan-ignore missingType.generics
    {
        $this->filter->between($field, $min, $max);
        return $this;
    }

    /**
     * Add a NOT BETWEEN condition.
     *
     * @param string $field Field name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     */
    public function notBetween(string $field, mixed $min, mixed $max): self // @phpstan-ignore missingType.generics
    {
        $this->filter->notBetween($field, $min, $max);
        return $this;
    }

    /**
     * Add a WHERE IN condition.
     *
     * @param string $field Field name
     * @param array<int, mixed> $values Values
     */
    public function whereIn(string $field, array $values): self // @phpstan-ignore missingType.generics
    {
        $this->filter->whereIn($field, $values);
        return $this;
    }

    /**
     * Add a WHERE NOT IN condition.
     *
     * @param string $field Field name
     * @param array<int, mixed> $values Values
     */
    public function whereNotIn(string $field, array $values): self // @phpstan-ignore missingType.generics
    {
        $this->filter->whereNotIn($field, $values);
        return $this;
    }

    /**
     * Add a WHERE NULL condition.
     *
     * @param string $field Field name
     */
    public function whereNull(string $field): self // @phpstan-ignore missingType.generics
    {
        $this->filter->whereNull($field);
        return $this;
    }

    /**
     * Add a WHERE NOT NULL condition.
     *
     * @param string $field Field name
     */
    public function whereNotNull(string $field): self // @phpstan-ignore missingType.generics
    {
        $this->filter->whereNotNull($field);
        return $this;
    }

    /**
     * Add a LIKE condition.
     *
     * @param string $field Field name
     * @param string $pattern Pattern with % wildcards
     */
    public function like(string $field, string $pattern): self // @phpstan-ignore missingType.generics
    {
        $this->filter->like($field, $pattern);
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $field Field name
     * @param string $direction Direction ('ASC' or 'DESC')
     */
    public function orderBy(string $field, string $direction = 'ASC'): self // @phpstan-ignore missingType.generics
    {
        $this->filter->orderBy($field, $direction);
        return $this;
    }

    /**
     * Add a LIMIT clause.
     *
     * @param int $limit Maximum number of results
     */
    public function limit(int $limit): self // @phpstan-ignore missingType.generics
    {
        $this->filter->limit($limit);
        return $this;
    }

    /**
     * Add an OFFSET clause.
     *
     * @param int $offset Number of results to skip
     */
    public function offset(int $offset): self // @phpstan-ignore missingType.generics
    {
        $this->filter->offset($offset);
        return $this;
    }

    /**
     * Add a DISTINCT clause.
     *
     * @param string $field Field name
     */
    public function distinct(string $field): self // @phpstan-ignore missingType.generics
    {
        $this->filter->distinct($field);
        return $this;
    }

    /**
     * Execute the query and return results as DataCollection.
     *
     * @return DataCollection<TValue>
     * @phpstan-ignore return.type
     */
    public function get(): DataCollection
    {
        $result = $this->filter->get();

        // Ensure result is array
        if (!is_array($result)) {
            $result = [];
        }

        return DataCollection::make($result); // @phpstan-ignore return.type
    }

    /**
     * Get the first result or null.
     *
     * @return mixed First result or null
     */
    public function first(): mixed
    {
        return $this->filter->first();
    }

    /** Count the number of results. */
    public function count(): int
    {
        return $this->filter->count();
    }
}
