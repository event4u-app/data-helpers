<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Helper for working with different collection types (Laravel, Doctrine).
 */
class CollectionHelper
{
    /**
     * Check if value is a Laravel Collection.
     */
    public static function isLaravelCollection(mixed $value): bool
    {
        return class_exists(\Illuminate\Support\Collection::class)
            && $value instanceof \Illuminate\Support\Collection;
    }

    /**
     * Check if value is a Doctrine Collection.
     */
    public static function isDoctrineCollection(mixed $value): bool
    {
        return interface_exists(\Doctrine\Common\Collections\Collection::class)
            && $value instanceof \Doctrine\Common\Collections\Collection;
    }

    /**
     * Check if value is any supported collection type.
     */
    public static function isCollection(mixed $value): bool
    {
        return self::isLaravelCollection($value) || self::isDoctrineCollection($value);
    }

    /**
     * Convert any collection to array.
     *
     * @return array<int|string, mixed>
     */
    public static function toArray(mixed $collection): array
    {
        if (self::isLaravelCollection($collection)) {
            return $collection->all();
        }

        if (self::isDoctrineCollection($collection)) {
            return $collection->toArray();
        }

        return [];
    }

    /**
     * Check if collection has a key.
     */
    public static function has(mixed $collection, string|int $key): bool
    {
        if (self::isLaravelCollection($collection)) {
            return $collection->has($key);
        }

        if (self::isDoctrineCollection($collection)) {
            return $collection->containsKey($key);
        }

        return false;
    }

    /**
     * Get value from collection by key.
     */
    public static function get(mixed $collection, string|int $key, mixed $default = null): mixed
    {
        if (self::isLaravelCollection($collection)) {
            return $collection->get($key, $default);
        }

        if (self::isDoctrineCollection($collection)) {
            $value = $collection->get($key);
            return $value ?? $default;
        }

        return $default;
    }

    /**
     * Create a collection from array (prefers Laravel if available).
     *
     * @param array<int|string, mixed> $data
     */
    public static function fromArray(array $data): mixed
    {
        if (class_exists(\Illuminate\Support\Collection::class)) {
            return new \Illuminate\Support\Collection($data);
        }

        if (class_exists(\Doctrine\Common\Collections\ArrayCollection::class)) {
            return new \Doctrine\Common\Collections\ArrayCollection($data);
        }

        return $data;
    }
}

