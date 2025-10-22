<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * DataMapperDelete - Fluent API for deleting template operators.
 *
 * Provides methods to delete specific operators from the template.
 *
 * Example:
 *   $mapper->delete()->all();           // Delete all operators
 *   $mapper->delete()->where();         // Delete WHERE conditions
 *   $mapper->delete()->where()->orderBy(); // Delete multiple operators
 */
final readonly class DataMapperDelete
{
    public function __construct(
        private FluentDataMapper $mapper
    ) {
    }

    /**
     * Delete all operators from template.
     *
     * @return self For fluent chaining
     */
    public function all(): self
    {
        $this->mapper->deleteTemplateOperator('WHERE');
        $this->mapper->deleteTemplateOperator('ORDER BY');
        $this->mapper->deleteTemplateOperator('LIMIT');
        $this->mapper->deleteTemplateOperator('OFFSET');
        $this->mapper->deleteTemplateOperator('GROUP BY');
        $this->mapper->deleteTemplateOperator('DISTINCT');
        $this->mapper->deleteTemplateOperator('LIKE');

        return $this;
    }

    /**
     * Delete WHERE conditions from template.
     *
     * @return self For fluent chaining
     */
    public function where(): self
    {
        $this->mapper->deleteTemplateOperator('WHERE');

        return $this;
    }

    /**
     * Delete ORDER BY conditions from template.
     *
     * @return self For fluent chaining
     */
    public function orderBy(): self
    {
        $this->mapper->deleteTemplateOperator('ORDER BY');

        return $this;
    }

    /**
     * Delete LIMIT from template.
     *
     * @return self For fluent chaining
     */
    public function limit(): self
    {
        $this->mapper->deleteTemplateOperator('LIMIT');

        return $this;
    }

    /**
     * Delete OFFSET from template.
     *
     * @return self For fluent chaining
     */
    public function offset(): self
    {
        $this->mapper->deleteTemplateOperator('OFFSET');

        return $this;
    }

    /**
     * Delete GROUP BY from template.
     *
     * @return self For fluent chaining
     */
    public function groupBy(): self
    {
        $this->mapper->deleteTemplateOperator('GROUP BY');

        return $this;
    }

    /**
     * Delete DISTINCT from template.
     *
     * @return self For fluent chaining
     */
    public function distinct(): self
    {
        $this->mapper->deleteTemplateOperator('DISTINCT');

        return $this;
    }

    /**
     * Delete LIKE from template.
     *
     * @return self For fluent chaining
     */
    public function like(): self
    {
        $this->mapper->deleteTemplateOperator('LIKE');

        return $this;
    }

    /** End delete chain and return to mapper. */
    public function end(): FluentDataMapper
    {
        return $this->mapper;
    }
}
