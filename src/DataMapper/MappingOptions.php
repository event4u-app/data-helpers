<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Configuration options for DataMapper operations.
 *
 * This immutable DTO encapsulates all mapping configuration options,
 * providing a cleaner API and easier extensibility.
 *
 * Example usage:
 * ```php
 * // Use defaults
 * $result = DataMapper::map($source, $target, $mapping, MappingOptions::default());
 *
 * // Customize options
 * $options = MappingOptions::default()
 *     ->withSkipNull(false)
 *     ->withTrimValues(false);
 * $result = DataMapper::map($source, $target, $mapping, $options);
 *
 * // Create from scratch
 * $options = new MappingOptions(
 *     skipNull: false,
 *     reindexWildcard: true,
 *     hooks: ['beforeAll' => fn() => ...],
 *     trimValues: false,
 *     caseInsensitiveReplace: true
 * );
 * ```
 */
final readonly class MappingOptions
{
    /**
     * Create new mapping options.
     *
     * @param bool $skipNull Skip null values during mapping (default: true)
     * @param bool $reindexWildcard Reindex wildcard results sequentially (default: false)
     * @param array<(DataMapperHook|string), mixed> $hooks Optional hooks for mapping lifecycle
     * @param bool $trimValues Trim string values during mapping (default: true)
     * @param bool $caseInsensitiveReplace Case insensitive string replacement (default: false)
     */
    public function __construct(
        public bool $skipNull = true,
        public bool $reindexWildcard = false,
        public array $hooks = [],
        public bool $trimValues = true,
        public bool $caseInsensitiveReplace = false,
    ) {}

    /**
     * Create default mapping options.
     *
     * @return self Default options with skipNull=true, trimValues=true, all others false/empty
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create options with skipNull disabled.
     *
     * @return self Options with skipNull=false
     */
    public static function includeNull(): self
    {
        return new self(false);
    }

    /**
     * Create options with reindexWildcard enabled.
     *
     * @return self Options with reindexWildcard=true
     */
    public static function reindexed(): self
    {
        return new self(true, true);
    }

    /**
     * Create a new instance with modified skipNull setting.
     *
     * @param bool $skipNull Whether to skip null values
     * @return self New instance with updated setting
     */
    public function withSkipNull(bool $skipNull): self
    {
        return new self(
            $skipNull,
            $this->reindexWildcard,
            $this->hooks,
            $this->trimValues,
            $this->caseInsensitiveReplace
        );
    }

    /**
     * Create a new instance with modified reindexWildcard setting.
     *
     * @param bool $reindexWildcard Whether to reindex wildcard results
     * @return self New instance with updated setting
     */
    public function withReindexWildcard(bool $reindexWildcard): self
    {
        return new self(
            $this->skipNull,
            $reindexWildcard,
            $this->hooks,
            $this->trimValues,
            $this->caseInsensitiveReplace
        );
    }

    /**
     * Create a new instance with modified hooks.
     *
     * @param array<(DataMapperHook|string), mixed> $hooks Mapping lifecycle hooks
     * @return self New instance with updated hooks
     */
    public function withHooks(array $hooks): self
    {
        return new self(
            $this->skipNull,
            $this->reindexWildcard,
            $hooks,
            $this->trimValues,
            $this->caseInsensitiveReplace
        );
    }

    /**
     * Create a new instance with an additional hook.
     *
     * @param DataMapperHook|string $hook Hook name
     * @param mixed $callback Hook callback
     * @return self New instance with added hook
     */
    public function withHook(DataMapperHook|string $hook, mixed $callback): self
    {
        $hooks = $this->hooks;
        $hookKey = $hook instanceof DataMapperHook ? $hook->value : $hook;
        $hooks[$hookKey] = $callback; // @phpstan-ignore-line offsetAccess.invalidOffset

        return new self(
            $this->skipNull,
            $this->reindexWildcard,
            $hooks,
            $this->trimValues,
            $this->caseInsensitiveReplace
        );
    }

    /**
     * Create a new instance with modified trimValues setting.
     *
     * @param bool $trimValues Whether to trim string values
     * @return self New instance with updated setting
     */
    public function withTrimValues(bool $trimValues): self
    {
        return new self(
            $this->skipNull,
            $this->reindexWildcard,
            $this->hooks,
            $trimValues,
            $this->caseInsensitiveReplace
        );
    }

    /**
     * Create a new instance with modified caseInsensitiveReplace setting.
     *
     * @param bool $caseInsensitiveReplace Whether to use case insensitive replacement
     * @return self New instance with updated setting
     */
    public function withCaseInsensitiveReplace(bool $caseInsensitiveReplace): self
    {
        return new self(
            $this->skipNull,
            $this->reindexWildcard,
            $this->hooks,
            $this->trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Convert options to array format (for backward compatibility).
     *
     * @return array{skipNull: bool, reindexWildcard: bool, hooks: array<(DataMapperHook|string), mixed>, trimValues: bool, caseInsensitiveReplace: bool}
     */
    public function toArray(): array
    {
        return [
            'skipNull' => $this->skipNull,
            'reindexWildcard' => $this->reindexWildcard,
            'hooks' => $this->hooks,
            'trimValues' => $this->trimValues,
            'caseInsensitiveReplace' => $this->caseInsensitiveReplace,
        ];
    }
}
