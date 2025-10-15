<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * OFFSET operator for skipping items.
 *
 * Skips the first N items.
 */
final class OffsetOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'OFFSET';
    }

    protected function getConfigSchema(): array
    {
        // OFFSET uses array_slice, doesn't use simple schema
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        // OFFSET operator overrides apply() completely, this method is not used
        return true;
    }

    // Override apply() for array_slice logic
    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_int($config) || 0 >= $config) {
            return $items;
        }

        return array_slice($items, $config, null, true);
    }
}

