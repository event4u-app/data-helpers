<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\DataMapper\DataMapperQuery;
use event4u\DataHelpers\DataMapper\MappingOptions;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Pipeline\ReverseDataMapperPipeline;
use event4u\DataHelpers\DataMapper\Support\MappingReverser;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * ReverseDataMapper provides reverse mapping functionality.
 *
 * Uses the same API as DataMapper but reverses the mapping direction:
 * - What was the target becomes the source
 * - What was the source becomes the target
 * - Templates are reversed automatically
 *
 * This allows using the same mapping/template for bidirectional mapping.
 *
 * Example:
 *   // Forward mapping
 *   $dto = DataMapper::map($user, [], ['full_name' => '{{ firstName }}']);
 *   // $dto = ['full_name' => 'John']
 *
 *   // Reverse mapping
 *   $user = ReverseDataMapper::map($dto, [], ['full_name' => '{{ firstName }}']);
 *   // $user = ['firstName' => 'John']
 */
class ReverseDataMapper
{
    /**
     * Map values from source to target using reversed mappings.
     *
     * The mapping is automatically reversed before being applied.
     *
     * Example:
     *   $mapping = ['full_name' => '{{ firstName }}', 'email' => '{{ contact.email }}'];
     *
     *   // Forward: user -> dto
     *   $dto = DataMapper::map($user, [], $mapping);
     *
     *   // Reverse: dto -> user
     *   $user = ReverseDataMapper::map($dto, [], $mapping);
     *
     * @param mixed $source The source data
     * @param mixed $target The target data
     * @param array<int|string, mixed> $mapping The mapping (will be reversed)
     * @param bool|MappingOptions $skipNull Skip null values
     * @param bool $reindexWildcard Reindex wildcard results
     * @param array<(DataMapperHook|string), mixed> $hooks Optional hooks
     * @param bool $trimValues Trim string values
     * @param bool $caseInsensitiveReplace Case insensitive replace
     * @return mixed The updated target
     */
    public static function map(
        mixed $source,
        mixed $target,
        array $mapping,
        bool|MappingOptions $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): mixed {
        // Reverse the mapping
        $reversedMapping = MappingReverser::reverseMapping($mapping);

        // Use DataMapper with reversed mapping
        return DataMapper::map(
            $source,
            $target,
            $reversedMapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Map from a template with reversed paths.
     *
     * Example:
     *   $template = [
     *       'profile' => [
     *           'name' => 'user.name',
     *           'email' => 'user.email',
     *       ],
     *   ];
     *
     *   // Forward: sources -> template structure
     *   $result = DataMapper::mapFromTemplate($template, $sources);
     *   // $result = ['profile' => ['name' => 'John', 'email' => 'john@example.com']]
     *
     *   // Reverse: template structure -> sources
     *   $sources = ReverseDataMapper::mapFromTemplate($template, $data);
     *   // $sources = ['user' => ['name' => 'John', 'email' => 'john@example.com']]
     *
     * @param array<string|int, mixed> $template The template (will be reversed)
     * @param array<string, mixed> $sources The source data
     * @param bool $skipNull Skip null values
     * @param bool $reindexWildcard Reindex wildcard results
     * @param bool $trimValues Trim string values
     * @return array<string, mixed> The mapped result
     */
    public static function mapFromTemplate(
        array $template,
        array $sources,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        bool $trimValues = true,
    ): array {
        // Reverse the template
        $reversedTemplate = MappingReverser::reverseTemplate($template);

        // Use DataMapper with reversed template
        return DataMapper::mapFromTemplate(
            $reversedTemplate,
            $sources,
            $skipNull,
            $reindexWildcard
        );
    }

    /**
     * Map data to targets using a reversed template.
     *
     * This is the reverse of mapFromTemplate:
     * - mapFromTemplate: reads from sources using template paths -> creates template structure
     * - mapToTargetsFromTemplate: reads from template structure -> writes to targets using template paths
     *
     * Example:
     *   $template = [
     *       'profile' => [
     *           'name' => 'user.name',
     *           'email' => 'user.email',
     *       ],
     *   ];
     *
     *   // Forward: sources -> template structure
     *   $result = DataMapper::mapFromTemplate($template, $sources);
     *   // $result = ['profile' => ['name' => 'John', 'email' => 'john@example.com']]
     *
     *   // Reverse: template structure -> targets
     *   $data = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
     *   $targets = ['user' => ['name' => null, 'email' => null]];
     *   $result = ReverseDataMapper::mapToTargetsFromTemplate($data, $template, $targets);
     *   // $result = ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
     *
     * @param array<string, mixed> $data Data with template structure
     * @param array<string, mixed> $template The template (NOT reversed - we use it as-is)
     * @param array<string, mixed> $targets Map of target name => target data
     * @param bool $skipNull Skip null values
     * @param bool $reindexWildcard Reindex wildcard results
     * @return array<string, mixed> Updated targets
     */
    public static function mapToTargetsFromTemplate(
        array $data,
        array $template,
        array $targets,
        bool $skipNull = true,
        bool $reindexWildcard = false,
    ): array {
        // For reverse mapping, we DON'T reverse the template
        // mapToTargetsFromTemplate already does what we need:
        // it reads from data (template structure) and writes to targets (using template paths)
        return DataMapper::mapToTargetsFromTemplate(
            $data,
            $template,
            $targets,
            $skipNull,
            $reindexWildcard
        );
    }

    /**
     * Auto-map by matching field names (reverse direction).
     *
     * Note: autoMap is symmetric, so reverse autoMap is the same as forward autoMap.
     * This method is provided for API consistency.
     *
     * @param mixed $source The source data
     * @param mixed $target The target data
     * @param bool $deep Enable deep mode
     * @param bool $skipNull Skip null values
     * @return mixed The updated target
     */
    public static function autoMap(
        mixed $source,
        mixed $target,
        bool $deep = false,
        bool $skipNull = true,
    ): mixed {
        // autoMap is symmetric, so just delegate to DataMapper
        return DataMapper::autoMap($source, $target, $deep, $skipNull);
    }

    /**
     * Create a pipeline with transformers for fluent reverse mapping.
     *
     * Example:
     *   ReverseDataMapper::pipe([
     *       new TrimStrings(),
     *       new LowercaseEmails(),
     *   ])->map($source, $target, $mapping);
     *
     * @param array<int, FilterInterface> $filters Filter instances
     */
    public static function pipe(array $filters): ReverseDataMapperPipeline
    {
        return new ReverseDataMapperPipeline($filters);
    }

    /**
     * Create a fluent query builder for reverse data mapping.
     *
     * Note: Query builder doesn't support reverse mapping yet.
     * This method is provided for future compatibility.
     */
    public static function query(): DataMapperQuery
    {
        // For now, just return a regular query
        // In the future, we could create a ReverseDataMapperQuery
        return DataMapper::query();
    }

    /**
     * Get the number of collected exceptions.
     */
    public static function getExceptionCount(): int
    {
        return DataMapper::getExceptionCount();
    }
}

