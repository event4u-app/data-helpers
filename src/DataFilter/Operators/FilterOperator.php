<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * FILTER Operator - Filter items using a callback.
 *
 * Configuration: callable
 *
 * Example:
 *   FILTER => fn($item) => $item['active'] === true
 *   Result: Only items where callback returns true are kept
 */
final class FilterOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'FILTER';
    }

    protected function getConfigSchema(): array
    {
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        return true;
    }

    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_callable($config)) {
            return $items;
        }

        return array_values(array_filter($items, $config));
    }
}

