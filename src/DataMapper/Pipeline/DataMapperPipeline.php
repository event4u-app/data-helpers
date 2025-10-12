<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper;

final class DataMapperPipeline
{
    /** @var array<string, mixed> */
    private array $additionalHooks = [];

    /** @param array<int, FilterInterface|class-string<FilterInterface>> $filters */
    public function __construct(private array $filters = []) {}

    /** @param FilterInterface|class-string<FilterInterface> $filter */
    public function through(FilterInterface|string $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /** @param array<string, mixed> $hooks */
    public function withHooks(array $hooks): self
    {
        $this->additionalHooks = array_merge($this->additionalHooks, $hooks);
        return $this;
    }

    /**
     * Execute the mapping with the configured pipeline.
     *
     * @param array<int|string, mixed> $mapping
     * @phpstan-return array<string, mixed>
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
        $hooks = $this->buildHooks();
        /** @phpstan-ignore-next-line return.type */
        return (array)DataMapper::map(
            $source,
            $target,
            $mapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Map using a template structure with the configured pipeline.
     *
     * @param array<string, mixed> $template Template structure
     * @param array<string, mixed> $sources Named sources
     * @phpstan-return array<string, mixed>
     */
    public function mapFromTemplate(
        array $template,
        array $sources,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): array {
        $hooks = $this->buildHooks();

        /** @phpstan-ignore-next-line return.type */
        return DataMapper::mapFromTemplate(
            $template,
            $sources,
            $skipNull,
            $reindexWildcard
        );
    }

    /**
     * Load data from a file (XML or JSON) and use it as source for mapping with the configured pipeline.
     *
     * @param array<int|string, mixed> $mapping
     */
    public function mapFromFile(
        string $filePath,
        mixed $target,
        array $mapping,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): mixed {
        $hooks = $this->buildHooks();

        return DataMapper::mapFromFile(
            $filePath,
            $target,
            $mapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /** @phpstan-return array<string, mixed> */
    private function buildHooks(): array
    {
        $hooks = $this->additionalHooks;

        foreach ($this->filters as $filter) {
            if (is_string($filter)) {
                $filter = new $filter();
            }

            $hookName = $filter->getHook();
            $filterName = $filter->getFilter();
            $callback = fn($value, $context) => $filter->transform($value, $context);

            if (null !== $filterName) {
                if (!isset($hooks[$hookName])) {
                    $hooks[$hookName] = [];
                }
                if (!is_array($hooks[$hookName])) {
                    $hooks[$hookName] = [$hooks[$hookName]];
                }
                $hooks[$hookName][$filterName] = $callback;
            } elseif (!isset($hooks[$hookName])) {
                $hooks[$hookName] = $callback;
            } elseif (is_callable($hooks[$hookName])) {
                $hooks[$hookName] = [$hooks[$hookName], $callback];
            } elseif (is_array($hooks[$hookName])) {
                $hooks[$hookName][] = $callback;
            }
        }

        return $hooks;
    }
}
