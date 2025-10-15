<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * Context for operator execution.
 *
 * Provides access to source data and configuration for value resolution.
 * Also stores operator-specific values resolved from config.
 */
final class OperatorContext
{
    /**
     * @param mixed $source Source data for template evaluation (for wildcard mode)
     * @param mixed $target Target data for template evaluation (for wildcard mode)
     * @param bool $isWildcardMode Whether operating in wildcard mode (template paths) or direct mode (field names)
     * @param array<int|string, mixed> $originalItems Original items before any operators applied
     * @param array<string, mixed> $values Operator-specific values (e.g., 'min', 'max', 'pattern')
     */
    public function __construct(
        public readonly mixed $source = null,
        public readonly mixed $target = null,
        public readonly bool $isWildcardMode = false,
        public readonly array $originalItems = [],
        private array $values = [],
    ) {}

    /**
     * Create context for wildcard mode (template paths like {{ products.*.price }}).
     *
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @param array<int|string, mixed> $originalItems Original items
     */
    public static function forWildcard(mixed $source, mixed $target, array $originalItems = []): self
    {
        return new self($source, $target, true, $originalItems);
    }

    /**
     * Create context for direct mode (field names like 'price').
     *
     * @param array<int|string, mixed> $items Items being filtered
     */
    public static function forDirect(array $items): self
    {
        return new self(null, null, false, $items);
    }

    /**
     * Get an operator-specific value.
     *
     * @param string $key Value key (e.g., 'min', 'max', 'pattern')
     * @param mixed $default Default value if key not found
     * @return mixed The value or default
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Create a new context with operator-specific values.
     *
     * @param array<string, mixed> $values Operator-specific values
     * @return self New context instance
     */
    public function withValues(array $values): self
    {
        return new self(
            $this->source,
            $this->target,
            $this->isWildcardMode,
            $this->originalItems,
            $values
        );
    }
}

