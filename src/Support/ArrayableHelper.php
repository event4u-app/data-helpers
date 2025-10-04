<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Helper for working with Arrayable interface (Laravel).
 */
class ArrayableHelper
{
    /** Check if value implements Arrayable interface. */
    public static function isArrayable(mixed $value): bool
    {
        return interface_exists('\Illuminate\Contracts\Support\Arrayable')
            && $value instanceof \Illuminate\Contracts\Support\Arrayable;
    }

    /**
     * Convert Arrayable to array.
     *
     * @return array<int|string, mixed>
     */
    public static function toArray(mixed $value): array
    {
        if (self::isArrayable($value)) {
            return $value->toArray();
        }

        return [];
    }
}
