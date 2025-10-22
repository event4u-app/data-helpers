<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * WHERE operator for filtering items.
 *
 * Supports comparison operators, AND/OR logic, and nested conditions.
 */
final class WhereOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'WHERE';
    }

    protected function getConfigSchema(): array
    {
        // WHERE has complex config with AND/OR logic, doesn't use simple schema
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        // WHERE operator overrides apply() completely, this method is not used
        return true;
    }

    // Override apply() for complex AND/OR logic
    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_array($config)) {
            return $items;
        }

        $filtered = [];

        foreach ($items as $index => $item) {
            if ($this->matchesCondition($config, $index, $item, $context)) {
                $filtered[$index] = $item;
            }
        }

        return $filtered;
    }

    /**
     * Check if an item matches the WHERE condition.
     *
     * @param array<int|string, mixed> $condition WHERE condition
     * @param int|string $index Current item index
     * @param mixed $item Current item data
     * @param OperatorContext $context Context for resolution
     */
    private function matchesCondition(array $condition, int|string $index, mixed $item, OperatorContext $context): bool
    {
        // Check if condition has only one key and it's AND or OR
        if (1 === count($condition)) {
            $key = array_key_first($condition);
            $keyUpper = strtoupper((string)$key);
            $value = $condition[$key];

            if ('AND' === $keyUpper) {
                assert(is_array($value));
                return $this->matchesAndCondition($value, $index, $item, $context);
            }

            if ('OR' === $keyUpper) {
                assert(is_array($value));
                return $this->matchesOrCondition($value, $index, $item, $context);
            }
        }

        // Multiple keys or no explicit AND/OR - treat as implicit AND
        return $this->matchesAndCondition($condition, $index, $item, $context);
    }

    /**
     * Check if item matches AND condition (all conditions must match).
     *
     * @param array<int|string, mixed> $conditions AND conditions
     * @param int|string $index Current item index
     * @param mixed $item Current item data
     * @param OperatorContext $context Context for resolution
     */
    private function matchesAndCondition(
        array $conditions,
        int|string $index,
        mixed $item,
        OperatorContext $context
    ): bool {
        foreach ($conditions as $key => $expectedValue) {
            $keyUpper = strtoupper((string)$key);

            // Handle nested OR within AND
            if ('OR' === $keyUpper && is_array($expectedValue)) {
                /** @var array<int|string, mixed> $expectedValue */
                if (!$this->matchesOrCondition($expectedValue, $index, $item, $context)) {
                    return false;
                }
                continue;
            }

            // Handle nested AND within AND
            if ('AND' === $keyUpper && is_array($expectedValue)) {
                /** @var array<int|string, mixed> $expectedValue */
                if (!$this->matchesAndCondition($expectedValue, $index, $item, $context)) {
                    return false;
                }
                continue;
            }

            // Handle array of conditions (for multiple OR groups)
            if (is_int($key) && is_array($expectedValue)) {
                if (!$this->matchesCondition($expectedValue, $index, $item, $context)) {
                    return false;
                }
                continue;
            }

            // Regular field comparison
            if (!$this->matchesFieldCondition((string)$key, $expectedValue, $index, $item, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if item matches OR condition (at least one condition must match).
     *
     * @param array<int|string, mixed> $conditions OR conditions
     * @param int|string $index Current item index
     * @param mixed $item Current item data
     * @param OperatorContext $context Context for resolution
     */
    private function matchesOrCondition(
        array $conditions,
        int|string $index,
        mixed $item,
        OperatorContext $context
    ): bool {
        foreach ($conditions as $key => $expectedValue) {
            $keyUpper = strtoupper((string)$key);

            // Handle nested AND within OR
            if ('AND' === $keyUpper && is_array($expectedValue)) {
                /** @var array<int|string, mixed> $expectedValue */
                if ($this->matchesAndCondition($expectedValue, $index, $item, $context)) {
                    return true;
                }
                continue;
            }

            // Handle nested OR within OR
            if ('OR' === $keyUpper && is_array($expectedValue)) {
                /** @var array<int|string, mixed> $expectedValue */
                if ($this->matchesOrCondition($expectedValue, $index, $item, $context)) {
                    return true;
                }
                continue;
            }

            // Handle array of conditions (for multiple OR groups)
            if (is_int($key) && is_array($expectedValue)) {
                if ($this->matchesCondition($expectedValue, $index, $item, $context)) {
                    return true;
                }
                continue;
            }

            // Regular field comparison
            if ($this->matchesFieldCondition((string)$key, $expectedValue, $index, $item, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a field matches the expected value.
     *
     * @param string $fieldPath Field path (may be template expression in wildcard mode)
     * @param mixed $expectedValue Expected value (may be array with operator)
     * @param int|string $index Current item index
     * @param mixed $item Current item data
     * @param OperatorContext $context Context for resolution
     */
    private function matchesFieldCondition(
        string $fieldPath,
        mixed $expectedValue,
        int|string $index,
        mixed $item,
        OperatorContext $context
    ): bool {
        // Get actual value from item
        $actualValue = $this->resolveFieldValue($fieldPath, $index, $item, $context);

        // Check if expectedValue is an array with operator and value
        if (is_array($expectedValue) && isset($expectedValue[0]) && is_string($expectedValue[0])) {
            $operator = $expectedValue[0];
            $compareValue = $expectedValue[1] ?? null;

            // Resolve compare value (might be template expression)
            $compareValue = $this->resolveConfigValue($compareValue, $context);

            return $this->compareValues($actualValue, $operator, $compareValue);
        }

        // Resolve expected value (might be template expression)
        $expectedValue = $this->resolveConfigValue($expectedValue, $context);

        // Simple equality check
        return $actualValue == $expectedValue;
    }
}
