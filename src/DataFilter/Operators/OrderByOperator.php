<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * ORDER BY operator for sorting items.
 *
 * Supports single and multiple field sorting with ASC/DESC directions.
 */
final class OrderByOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'ORDER BY';
    }

    public function getAliases(): array
    {
        return ['ORDER'];
    }

    protected function getConfigSchema(): array
    {
        // ORDER BY has complex sorting logic, doesn't use simple schema
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        // ORDER BY operator overrides apply() completely, this method is not used
        return true;
    }

    // Override apply() for sorting logic
    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_array($config)) {
            return $items;
        }

        // Convert items to array for sorting
        $sortable = $items;

        // Sort using usort with custom comparator
        uasort($sortable, function($a, $b) use ($config, $context, $items): int {
            // Get indices
            $indexA = array_search($a, $items, true);
            $indexB = array_search($b, $items, true);

            // array_search can return false, default to 0
            if (false === $indexA) {
                $indexA = 0;
            }
            if (false === $indexB) {
                $indexB = 0;
            }

            foreach ($config as $fieldPath => $direction) {
                $direction = strtoupper((string)$direction);

                // Get values for comparison
                $valueA = $this->resolveFieldValue($fieldPath, $indexA, $a, $context);
                $valueB = $this->resolveFieldValue($fieldPath, $indexB, $b, $context);

                // Compare values
                $comparison = $this->compareForSort($valueA, $valueB);

                if (0 !== $comparison) {
                    return 'DESC' === $direction ? -$comparison : $comparison;
                }
            }

            return 0;
        });

        return $sortable;
    }

    /** Compare two values for sorting. */
    private function compareForSort(mixed $a, mixed $b): int
    {
        // Handle null values (nulls come first in ASC order)
        if (null === $a && null === $b) {
            return 0;
        }
        if (null === $a) {
            return -1;
        }
        if (null === $b) {
            return 1;
        }

        // Numeric comparison
        if (is_numeric($a) && is_numeric($b)) {
            $numA = is_string($a) ? (float)$a : $a;
            $numB = is_string($b) ? (float)$b : $b;

            if ($numA < $numB) {
                return -1;
            }
            if ($numA > $numB) {
                return 1;
            }
            return 0;
        }

        // String comparison
        if (is_string($a) && is_string($b)) {
            return strcmp($a, $b);
        }

        // Mixed types - convert to string and compare
        $strA = (string)$a;
        $strB = (string)$b;
        return strcmp($strA, $strB);
    }
}

