<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * MAP Operator - Transform each item using a callback.
 *
 * Configuration: callable
 *
 * Example:
 *   MAP => fn($item) => ['name' => strtoupper($item['name'])]
 *   Result: Each item is transformed by the callback
 */
final class MapOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'MAP';
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

        return array_map($config, $items);
    }
}
