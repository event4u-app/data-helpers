<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * DISTINCT operator for removing duplicates.
 *
 * Removes duplicate items based on a field value.
 */
final class DistinctOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'DISTINCT';
    }

    protected function getConfigSchema(): array
    {
        // DISTINCT has custom deduplication logic, doesn't use simple schema
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        // DISTINCT operator overrides apply() completely, this method is not used
        return true;
    }

    // Override apply() for deduplication logic
    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_string($config)) {
            return $items;
        }

        $seen = [];
        $result = [];

        foreach ($items as $index => $item) {
            // Get value for distinctness check
            $value = $this->resolveFieldValue($config, $index, $item, $context);

            // Create a key for comparison
            $key = json_encode($value);

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[$index] = $item;
            }
        }

        return $result;
    }
}
