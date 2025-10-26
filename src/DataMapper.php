<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\DataMapper\DataMapperQuery;
use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * DataMapper - Facade for creating FluentDataMapper instances.
 *
 * This class provides ONLY factory methods for creating FluentDataMapper instances.
 * All actual mapping logic is in FluentDataMapper and internal helper classes.
 */
class DataMapper
{
    /**
     * Create a FluentDataMapper with source data (auto-detects files).
     *
     * If the source is a string and points to an existing file, it will be loaded automatically.
     *
     * @param mixed $source Source data (array, object, model, DTO, JSON, XML, or file path)
     */
    public static function source(mixed $source): FluentDataMapper
    {
        return self::make()->source($source);
    }

    /**
     * Alias for source() - Create a FluentDataMapper with source data.
     *
     * @param mixed $source Source data (array, object, model, DTO, JSON, XML, or file path)
     */
    public static function from(mixed $source): FluentDataMapper
    {
        return self::source($source);
    }

    /**
     * Create a FluentDataMapper from a file.
     *
     * @param string $filePath File path (JSON, XML, CSV, etc.)
     */
    public static function sourceFile(string $filePath): FluentDataMapper
    {
        return self::make()->sourceFile($filePath);
    }

    /**
     * Create a FluentDataMapper starting with a template.
     *
     * @param array<int|string, mixed> $template Mapping template
     */
    public static function template(array $template): FluentDataMapper
    {
        return self::make()->template($template);
    }

    /**
     * Create a FluentDataMapper starting with a target.
     *
     * @param mixed $target Target (Object, Array, String (JSON/XML), String (Klassenname))
     */
    public static function target(mixed $target): FluentDataMapper
    {
        return self::make()->target($target);
    }

    /**
     * Create a FluentDataMapper starting with a pipeline.
     *
     * @param array<int, FilterInterface> $filters Filter instances
     */
    public static function pipeline(array $filters): FluentDataMapper
    {
        return self::make()->pipeline($filters);
    }

    /** Create a DataMapperQuery. */
    public static function query(): DataMapperQuery
    {
        return self::createQuery();
    }

    /**
     * Create a DataMapperQuery starting with a pipeline.
     *
     * @param array<int, FilterInterface> $filters Filter instances
     */
    public static function queryPipeline(array $filters): DataMapperQuery
    {
        return self::createQuery()->pipeline($filters);
    }

    /** Create a new FluentDataMapper instance. */
    private static function make(): FluentDataMapper
    {
        return FluentDataMapper::make();
    }

    /** Create a new DataMapperQuery instance. */
    private static function createQuery(): DataMapperQuery
    {
        return new DataMapperQuery();
    }
}
