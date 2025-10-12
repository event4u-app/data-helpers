<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

/**
 * OFFSET operator for wildcard arrays.
 *
 * Skips the first N items in the array.
 */
class OffsetOperator
{
    /**
     * Skip the first N items in the array.
     *
     * @param array<int|string, mixed> $items Items to offset
     * @param mixed $config Offset configuration (integer)
     * @return array<int|string, mixed> Offset items
     */
    public static function apply(array $items, mixed $config): array
    {
        if (!is_int($config) || 0 > $config) {
            return $items;
        }

        $result = [];
        $count = 0;

        foreach ($items as $index => $item) {
            if ($count < $config) {
                $count++;
                continue;
            }
            $result[$index] = $item;
        }

        return $result;
    }
}

