<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper\Support\MappingReverser;

/**
 * Pipeline for reverse data mapping with filters.
 *
 * Applies filters to data during reverse mapping operations.
 */
class ReverseDataMapperPipeline
{
    /** @param array<int, FilterInterface> $filters */
    public function __construct(
        private readonly array $filters,
    ) {
    }

    /**
     * Map values from source to target using reversed mappings with filters.
     *
     * @param mixed $source The source data
     * @param mixed $target The target data
     * @param array<int|string, mixed> $mapping The mapping (will be reversed)
     * @param bool $skipNull Skip null values
     * @param bool $reindexWildcard Reindex wildcard results
     * @param bool $trimValues Trim string values
     * @param bool $caseInsensitiveReplace Case insensitive replace
     * @return array<string, mixed> The updated target
     */
    public function map(
        mixed $source,
        mixed $target,
        array $mapping,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): array {
        // Reverse the mapping
        $reversedMapping = MappingReverser::reverseMapping($mapping);

        // Use DataMapperPipeline with reversed mapping
        $pipeline = new DataMapperPipeline($this->filters);

        return $pipeline->map(
            $source,
            $target,
            $reversedMapping,
            $skipNull,
            $reindexWildcard,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Map from a template with reversed paths and filters.
     *
     * @param array<string|int, mixed> $template The template (will be reversed)
     * @param array<string, mixed> $sources The source data
     * @param bool $skipNull Skip null values
     * @param bool $reindexWildcard Reindex wildcard results
     * @param bool $trimValues Trim string values
     * @return array<string, mixed> The mapped result
     */
    public function mapFromTemplate(
        array $template,
        array $sources,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        bool $trimValues = true,
    ): array {
        // Reverse the template
        $reversedTemplate = MappingReverser::reverseTemplate($template);

        // Use DataMapperPipeline with reversed template
        $pipeline = new DataMapperPipeline($this->filters);

        return $pipeline->mapFromTemplate(
            $reversedTemplate,
            $sources,
            $skipNull,
            $reindexWildcard
        );
    }
}

