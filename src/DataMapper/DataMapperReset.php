<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * DataMapperReset - Fluent API for resetting template parts to original values.
 *
 * Provides methods to reset specific parts of the template to their original state
 * from $originalTemplate.
 *
 * Example:
 *   $mapper->reset()->all();           // Reset entire template
 *   $mapper->reset()->where();         // Reset WHERE conditions
 *   $mapper->reset()->where()->orderBy(); // Reset multiple parts
 */
final readonly class DataMapperReset
{
    public function __construct(
        private FluentDataMapper $mapper
    ) {
    }

    /**
     * Reset entire template to original template.
     *
     * @return self For fluent chaining
     */
    public function all(): self
    {
        $this->mapper->resetTemplateToOriginal();

        return $this;
    }

    /**
     * Alias for all() - reset entire template.
     *
     * @return self For fluent chaining
     */
    public function template(): self
    {
        return $this->all();
    }

    /**
     * Reset WHERE conditions to original template WHERE.
     *
     * @return self For fluent chaining
     */
    public function where(): self
    {
        $this->mapper->resetTemplateOperator('WHERE');

        return $this;
    }

    /**
     * Reset ORDER BY conditions to original template ORDER BY.
     *
     * @return self For fluent chaining
     */
    public function orderBy(): self
    {
        $this->mapper->resetTemplateOperator('ORDER BY');

        return $this;
    }

    /**
     * Reset LIMIT to original template LIMIT.
     *
     * @return self For fluent chaining
     */
    public function limit(): self
    {
        $this->mapper->resetTemplateOperator('LIMIT');

        return $this;
    }

    /**
     * Reset OFFSET to original template OFFSET.
     *
     * @return self For fluent chaining
     */
    public function offset(): self
    {
        $this->mapper->resetTemplateOperator('OFFSET');

        return $this;
    }

    /**
     * Reset GROUP BY to original template GROUP BY.
     *
     * @return self For fluent chaining
     */
    public function groupBy(): self
    {
        $this->mapper->resetTemplateOperator('GROUP BY');

        return $this;
    }

    /**
     * Reset DISTINCT to original template DISTINCT.
     *
     * @return self For fluent chaining
     */
    public function distinct(): self
    {
        $this->mapper->resetTemplateOperator('DISTINCT');

        return $this;
    }

    /**
     * Reset LIKE to original template LIKE.
     *
     * @return self For fluent chaining
     */
    public function like(): self
    {
        $this->mapper->resetTemplateOperator('LIKE');

        return $this;
    }

    /** End reset chain and return to mapper. */
    public function end(): FluentDataMapper
    {
        return $this->mapper;
    }
}
