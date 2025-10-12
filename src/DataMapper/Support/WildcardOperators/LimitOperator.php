<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

/**
 * LIMIT operator for wildcard arrays.
 *
 * Limits the number of items in the array.
 */
class LimitOperator
{
    /**
     * Limit the number of items in the array.
     *
     * @param array<int|string, mixed> $items Items to limit
     * @param mixed $config Limit configuration (integer)
     * @return array<int|string, mixed> Limited items
     */
    public static function apply(array $items, mixed $config): array
    {
        if (!is_int($config) || 0 > $config) {
            return $items;
        }

        $result = [];
        $count = 0;

        foreach ($items as $index => $item) {
            if ($count >= $config) {
                break;
            }
            $result[$index] = $item;
            $count++;
        }

        return $result;
    }
}

