<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Helper for working with Arrayable interface (Laravel).
 */
class ArrayableHelper
{
    /** @var null|bool */
    private static ?bool $arrayableExists = null;

    /** Check if value implements Arrayable interface. */
    public static function isArrayable(mixed $value): bool
    {
        // Cache interface_exists check for better performance
        self::$arrayableExists ??= interface_exists('\Illuminate\Contracts\Support\Arrayable');

        return self::$arrayableExists && $value instanceof \Illuminate\Contracts\Support\Arrayable;
    }

    /**
     * Convert Arrayable to array.
     *
     * @return array<int|string, mixed>
     */
    public static function toArray(mixed $value): array
    {
        if (self::isArrayable($value)) {
            /** @phpstan-ignore method.nonObject */
            return $value->toArray();
        }

        return [];
    }
}
