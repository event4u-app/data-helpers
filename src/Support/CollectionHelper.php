<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Helper for working with different collection types (Laravel, Doctrine).
 */
class CollectionHelper
{
    /** @var null|bool */
    private static ?bool $laravelCollectionExists = null;

    /** @var null|bool */
    private static ?bool $doctrineCollectionExists = null;

    /** Check if value is a Laravel Collection. */
    public static function isLaravelCollection(mixed $value): bool
    {
        // Cache class_exists check for better performance
        self::$laravelCollectionExists ??= class_exists('\Illuminate\Support\Collection');

        return self::$laravelCollectionExists && $value instanceof \Illuminate\Support\Collection;
    }

    /** Check if value is a Doctrine Collection. */
    public static function isDoctrineCollection(mixed $value): bool
    {
        // Cache interface_exists check for better performance
        self::$doctrineCollectionExists ??= interface_exists('\Doctrine\Common\Collections\Collection');

        return self::$doctrineCollectionExists && $value instanceof \Doctrine\Common\Collections\Collection;
    }

    /** Check if value is any supported collection type. */
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
            /** @phpstan-ignore method.nonObject */
            return $collection->all();
        }

        if (self::isDoctrineCollection($collection)) {
            /** @phpstan-ignore method.nonObject */
            return $collection->toArray();
        }

        return [];
    }

    /** Check if collection has a key. */
    public static function has(mixed $collection, int|string $key): bool
    {
        if (self::isLaravelCollection($collection)) {
            /** @phpstan-ignore method.nonObject */
            return $collection->has($key);
        }

        if (self::isDoctrineCollection($collection)) {
            /** @phpstan-ignore method.nonObject */
            return $collection->containsKey($key);
        }

        return false;
    }

    /** Get value from collection by key. */
    public static function get(mixed $collection, int|string $key, mixed $default = null): mixed
    {
        if (self::isLaravelCollection($collection)) {
            /** @phpstan-ignore method.nonObject */
            return $collection->get($key, $default);
        }

        if (self::isDoctrineCollection($collection)) {
            /** @phpstan-ignore method.nonObject */
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
        // Use cached class_exists checks
        if (self::$laravelCollectionExists ?? (self::$laravelCollectionExists = class_exists('\Illuminate\Support\Collection'))) {
            return new \Illuminate\Support\Collection($data);
        }

        if (self::$doctrineCollectionExists ?? (self::$doctrineCollectionExists = class_exists('\Doctrine\Common\Collections\ArrayCollection'))) {
            return new \Doctrine\Common\Collections\ArrayCollection($data);
        }

        return $data;
    }

    /**
     * Create a collection from array with the same type as the reference collection.
     *
     * @param array<int|string, mixed> $data
     * @param mixed $referenceCollection The collection to match the type of
     */
    public static function fromArrayWithType(array $data, mixed $referenceCollection): mixed
    {
        if (self::isLaravelCollection($referenceCollection)) {
            return new \Illuminate\Support\Collection($data);
        }

        if (self::isDoctrineCollection($referenceCollection)) {
            return new \Doctrine\Common\Collections\ArrayCollection($data);
        }

        return $data;
    }

    /**
     * Set value into collection by converting to array, modifying, and converting back.
     *
     * @param array<int, string> $segments
     * @param callable(array<int|string, mixed> $array, array<int, string> $segments, mixed $value, bool $merge): void $setCallback
     */
    public static function setIntoCollection(
        mixed $collection,
        array $segments,
        mixed $value,
        bool $merge,
        callable $setCallback,
    ): mixed {
        if (!self::isCollection($collection)) {
            return $collection;
        }

        $arr = self::toArray($collection);
        $setCallback($arr, $segments, $value, $merge);

        return self::fromArrayWithType($arr, $collection);
    }

    /**
     * Unset value from collection by converting to array, modifying, and converting back.
     *
     * @param array<int, string> $segments
     * @param callable(array<int|string, mixed> $array, array<int, string> $segments): void $unsetCallback
     */
    public static function unsetFromCollection(
        mixed $collection,
        array $segments,
        callable $unsetCallback,
    ): mixed {
        if (!self::isCollection($collection)) {
            return $collection;
        }

        $arr = self::toArray($collection);
        $unsetCallback($arr, $segments);

        return self::fromArrayWithType($arr, $collection);
    }
}
