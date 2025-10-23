<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * LIMIT operator for limiting results.
 *
 * Returns only the first N items.
 */
final class LimitOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'LIMIT';
    }

    protected function getConfigSchema(): array
    {
        // LIMIT uses array_slice, doesn't use simple schema
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        // LIMIT operator overrides apply() completely, this method is not used
        return true;
    }

    // Override apply() for array_slice logic
    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_int($config) || 0 > $config) {
            return $items;
        }

        // LIMIT 0 returns empty array
        if (0 === $config) {
            return [];
        }

        return array_slice($items, 0, $config, true);
    }
}
