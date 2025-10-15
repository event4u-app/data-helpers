<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * DataMapperProperty - Fluent API for property-specific operations.
 *
 * Provides a fluent interface for working with specific properties in the mapper.
 *
 * Example:
 *   $mapper->property('user.name')
 *       ->setFilter(new TrimStrings())
 *       ->end();
 *
 *   $filter = $mapper->property('user.name')->getFilter();
 *   $target = $mapper->property('user.name')->getTarget();
 *   $value = $mapper->property('user.name')->getMappedValue();
 */
final class DataMapperProperty
{
    private string $propertyPath;

    private FluentDataMapper $mapper;

    /**
     * Create a new DataMapperProperty instance.
     *
     * @param string $propertyPath Property path (dot-notation)
     * @param FluentDataMapper $mapper Parent mapper
     */
    public function __construct(string $propertyPath, FluentDataMapper $mapper)
    {
        $this->propertyPath = $propertyPath;
        $this->mapper = $mapper;
    }

    /**
     * Set filters for this property.
     *
     * Supports multiple call styles:
     *   - Single filter: setFilter($filter1)
     *   - Multiple filters as arguments: setFilter($filter1, $filter2)
     *   - Multiple filters as array: setFilter([$filter1, $filter2])
     *
     * @param FilterInterface|array<int, FilterInterface> ...$filters Filter instances
     */
    public function setFilter(FilterInterface|array ...$filters): self
    {
        $this->mapper->setValueFilters($this->propertyPath, ...$filters);

        return $this;
    }

    /**
     * Reset filters for this property.
     *
     * Removes all filters associated with this property.
     */
    public function resetFilter(): self
    {
        $this->mapper->resetPropertyFilters($this->propertyPath);

        return $this;
    }

    /**
     * Get filters for this property.
     *
     * @return array<int, FilterInterface>
     */
    public function getFilter(): array
    {
        return $this->mapper->getPropertyFilters($this->propertyPath);
    }

    /**
     * Get the mapping target for this property.
     *
     * Returns the template value that defines where this property maps to.
     * Returns null if property is not in template.
     *
     * @return mixed
     */
    public function getTarget(): mixed
    {
        return $this->mapper->getPropertyTarget($this->propertyPath);
    }

    /**
     * Get the mapped value for this property.
     *
     * Executes the mapping and returns the value for this specific property.
     * Returns null if property is not in result.
     *
     * @return mixed
     */
    public function getMappedValue(): mixed
    {
        return $this->mapper->getPropertyMappedValue($this->propertyPath);
    }

    /**
     * Return to the parent mapper.
     */
    public function end(): FluentDataMapper
    {
        return $this->mapper;
    }
}

