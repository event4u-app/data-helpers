<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\Support\ArrayableHelper;
use event4u\DataHelpers\Support\CollectionHelper;
use event4u\DataHelpers\Support\EntityHelper;
use event4u\DataHelpers\Support\ReflectionCache;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionProperty;

class DataMutator
{
    /**
     * Set one or multiple values into a target (array, DTO, Laravel model, Collection) using dot-notation.
     *
     * Example:
     *   DataMutator::set($data, 'user.name', 'Alice');
     *   DataMutator::set($data, ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']);
     *
     * @param array<int|string, mixed>|object $target
     * @param array<int|string, mixed>|string $pathOrValues
     * @param bool $merge Whether to deep-merge arrays instead of overwriting
     * @return array<int|string, mixed>|object
     */
    public static function set(
        array|object $target,
        array|string $pathOrValues,
        mixed $value = null,
        bool $merge = false,
    ): array|object {
        if (is_array($pathOrValues)) {
            foreach ($pathOrValues as $path => $val) {
                $segments = DotPathHelper::segments((string)$path);
                $target = self::applySet($target, $segments, $val, $merge);
            }

            return $target;
        }

        $segments = DotPathHelper::segments($pathOrValues);

        return self::applySet($target, $segments, $value, $merge);
    }

    /**
     * Shortcut for deep merge.
     *
     * @param array<int|string, mixed>|object $target
     * @param array<int|string, mixed>|string $pathOrValues
     * @return array<int|string, mixed>|object
     */
    public static function merge(array|object $target, array|string $pathOrValues, mixed $value = null): array|object
    {
        return self::set($target, $pathOrValues, $value, true);
    }

    /**
     * Apply setting into array, collection, model, or object.
     *
     * @param array<int|string, mixed>|object $target
     * @param array<int, string> $segments
     * @return array<int|string, mixed>|object
     */
    private static function applySet(array|object $target, array $segments, mixed $value, bool $merge): array|object
    {
        if (is_array($target)) {
            self::setIntoArray($target, $segments, $value, $merge);

            return $target;
        }

        // Support for any collection type (Laravel, Doctrine)
        if (CollectionHelper::isCollection($target)) {
            $arr = CollectionHelper::toArray($target);
            self::setIntoArray($arr, $segments, $value, $merge);

            $result = CollectionHelper::fromArrayWithType($arr, $target);

            /** @var array<int|string, mixed>|object $result */
            return $result;
        }

        // Support for any entity/model type (Eloquent, Doctrine)
        if (EntityHelper::isEntity($target)) {
            EntityHelper::setAttribute($target, implode('.', $segments), $value);

            return $target;
        }

        // Support for DTOs with public properties (only if the first segment exists as a property)
        if ([] !== $segments && EntityHelper::hasProperty($target, $segments[0])) {
            // If merge is true and value is an array, merge with existing value
            if ($merge && is_array($value)) {
                $existingValue = EntityHelper::getAttribute($target, implode('.', $segments));
                if (is_array($existingValue)) {
                    $value = array_replace_recursive($existingValue, $value);
                }
            }

            EntityHelper::setAttribute($target, implode('.', $segments), $value);

            return $target;
        }

        // Fallback for Arrayable interface
        if (ArrayableHelper::isArrayable($target)) {
            $arr = ArrayableHelper::toArray($target);
            self::setIntoArray($arr, $segments, $value, $merge);

            return $arr;
        }

        if ($target instanceof JsonSerializable) {
            $arr = (array)$target->jsonSerialize();
            self::setIntoArray($arr, $segments, $value, $merge);

            return $arr;
        }

        if (is_object($target)) {
            self::setIntoObject($target, $segments, $value, $merge);

            return $target;
        }

        throw new InvalidArgumentException('Unsupported target type: ' . gettype($target));
    }

    /**
     * Iterate over array items by reference and apply a callback.
     *
     * @param array<int|string, mixed> $array
     * @param callable(mixed& $item, int|string $key): void $callback
     * @phpstan-ignore ergebnis.noParameterPassedByReference
     */
    private static function forEachArrayItem(array &$array, callable $callback): void
    {
        foreach ($array as $key => &$item) {
            $callback($item, $key);
        }
        unset($item); // break ref
    }

    /**
     * Recursively set into arrays.
     *
     * @param array<int|string, mixed> $array
     * @param array<int, string> $segments
     * @phpstan-ignore ergebnis.noParameterPassedByReference
     */
    private static function setIntoArray(array &$array, array $segments, mixed $value, bool $merge): void
    {
        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }

        if (DotPathHelper::isWildcard($segment)) {
            /** @phpstan-ignore ergebnis.noParameterPassedByReference */
            self::forEachArrayItem($array, function(&$item, int|string $key) use ($segments, $value, $merge): void {
                if ([] === $segments) {
                    if ($merge && is_array($item) && is_array($value)) {
                        $item = self::deepMerge($item, $value);
                    } else {
                        $item = $value;
                    }
                } elseif (is_array($item)) {
                    self::setIntoArray($item, $segments, $value, $merge);
                } elseif (is_object($item)) {
                    self::setIntoObject($item, $segments, $value, $merge);
                }
            });

            return;
        }

        if (!array_key_exists($segment, $array)) {
            $array[$segment] = [];
        }

        if ([] === $segments) {
            if ($merge && is_array($array[$segment]) && is_array($value)) {
                $array[$segment] = self::deepMerge($array[$segment], $value);
            } else {
                $array[$segment] = $value;
            }

            return;
        }

        if (is_array($array[$segment])) {
            self::setIntoArray($array[$segment], $segments, $value, $merge);
        } elseif (is_object($array[$segment])) {
            self::setIntoObject($array[$segment], $segments, $value, $merge);
        } else {
            $array[$segment] = [];
            self::setIntoArray($array[$segment], $segments, $value, $merge);
        }
    }

    /**
     * Recursively set into objects.
     *
     * @param array<int, string> $segments
     */
    private static function setIntoObject(object $object, array $segments, mixed $value, bool $merge): void
    {
        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }

        $ref = ReflectionCache::getClass($object);
        if (!$ref->hasProperty($segment) && [] === $segments) {
            $object->{$segment} = $value;

            return;
        }

        $prop = ReflectionCache::getProperty($object, $segment);
        if ($prop instanceof ReflectionProperty) {
            if ([] === $segments) {
                $current = $prop->getValue($object);
                if ($merge && is_array($current) && is_array($value)) {
                    $prop->setValue($object, self::deepMerge($current, $value));
                } else {
                    $prop->setValue($object, $value);
                }

                return;
            }

            $current = $prop->getValue($object) ?? [];
            if (is_array($current)) {
                self::setIntoArray($current, $segments, $value, $merge);
            } elseif (CollectionHelper::isCollection($current)) {
                $current = CollectionHelper::setIntoCollection(
                    $current,
                    $segments,
                    $value,
                    $merge,
                    self::setIntoArray(...)
                );
                $prop->setValue($object, $current);

                return;
            } elseif (is_object($current)) {
                self::setIntoObject($current, $segments, $value, $merge);
            } else {
                $current = [];
                self::setIntoArray($current, $segments, $value, $merge);
            }

            $prop->setValue($object, $current);
        }
    }

    /**
     * Deep merge two arrays.
     *
     * @param array<int|string, mixed> $a
     * @param array<int|string, mixed> $b
     * @return array<int|string, mixed>
     */
    private static function deepMerge(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            if (is_int($key)) {
                $a[$key] = $value;

                continue;
            }

            if (array_key_exists($key, $a) && is_array($a[$key]) && is_array($value)) {
                $a[$key] = self::deepMerge($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * Unset one or multiple values using dot-notation.
     *
     * @param array<int|string, mixed>|object $target
     * @param array<int, string>|string $paths
     * @return array<int|string, mixed>|object
     */
    public static function unset(array|object $target, array|string $paths): array|object
    {
        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($paths as $path) {
            $segments = DotPathHelper::segments($path);

            if (is_array($target)) {
                self::unsetFromArray($target, $segments);

                continue;
            }

            // Support for any collection type (Laravel, Doctrine)
            if (CollectionHelper::isCollection($target)) {
                $arr = CollectionHelper::toArray($target);
                self::unsetFromArray($arr, $segments);
                $result = CollectionHelper::fromArrayWithType($arr, $target);

                /** @var array<int|string, mixed>|object $result */
                $target = $result;

                continue;
            }

            // Support for any entity/model type (Eloquent, Doctrine)
            if (EntityHelper::isEntity($target)) {
                EntityHelper::unsetFromEntity($target, $segments);

                continue;
            }

            // Fallback for Arrayable interface
            if (ArrayableHelper::isArrayable($target)) {
                $arr = ArrayableHelper::toArray($target);
                self::unsetFromArray($arr, $segments);
                $target = $arr;

                continue;
            }

            if ($target instanceof JsonSerializable) {
                $arr = (array)$target->jsonSerialize();
                self::unsetFromArray($arr, $segments);
                $target = $arr;

                continue;
            }

            if (is_object($target)) {
                self::unsetFromObject($target, $segments);

                continue;
            }

            throw new InvalidArgumentException('Unsupported target type: ' . gettype($target));
        }

        return $target;
    }

    /**
     * Recursively unset from arrays.
     *
     * @param array<int|string, mixed> $array
     * @param array<int, string> $segments
     * @phpstan-ignore ergebnis.noParameterPassedByReference
     */
    private static function unsetFromArray(array &$array, array $segments): void
    {
        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }

        if (DotPathHelper::isWildcard($segment)) {
            if ([] === $segments) {
                $array = [];

                return;
            }

            /** @phpstan-ignore ergebnis.noParameterPassedByReference */
            self::forEachArrayItem($array, function(&$item, int|string $key) use ($segments): void {
                if (is_array($item)) {
                    self::unsetFromArray($item, $segments);
                } elseif (EntityHelper::isEntity($item)) {
                    EntityHelper::unsetFromEntity($item, $segments);
                } elseif (CollectionHelper::isCollection($item)) {
                    $item = CollectionHelper::unsetFromCollection($item, $segments, self::unsetFromArray(...));
                } elseif (is_object($item)) {
                    self::unsetFromObject($item, $segments);
                }
            });

            return;
        }

        if (!array_key_exists($segment, $array)) {
            return;
        }

        if ([] === $segments) {
            unset($array[$segment]);

            return;
        }

        if (is_array($array[$segment])) {
            self::unsetFromArray($array[$segment], $segments);
        } elseif (EntityHelper::isEntity($array[$segment])) {
            EntityHelper::unsetFromEntity($array[$segment], $segments);
        } elseif (CollectionHelper::isCollection($array[$segment])) {
            $array[$segment] = CollectionHelper::unsetFromCollection(
                $array[$segment],
                $segments,
                self::unsetFromArray(...)
            );
        } elseif (is_object($array[$segment])) {
            self::unsetFromObject($array[$segment], $segments);
        }
    }

    /**
     * Recursively unset from generic objects.
     *
     * @param array<int, string> $segments
     */
    private static function unsetFromObject(object $object, array $segments): void
    {
        if (EntityHelper::isEntity($object)) {
            EntityHelper::unsetFromEntity($object, $segments);

            return;
        }

        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }
        $ref = ReflectionCache::getClass($object);

        if (!$ref->hasProperty($segment)) {
            return;
        }

        $prop = ReflectionCache::getProperty($object, $segment);
        if (!$prop instanceof ReflectionProperty) {
            return;
        }

        if ([] === $segments) {
            $prop->setValue($object, null);

            return;
        }

        $current = $prop->getValue($object);
        if (is_array($current)) {
            self::unsetFromArray($current, $segments);
            $prop->setValue($object, $current);
        } elseif (CollectionHelper::isCollection($current)) {
            $current = CollectionHelper::unsetFromCollection($current, $segments, self::unsetFromArray(...));
            $prop->setValue($object, $current);
        } elseif (EntityHelper::isEntity($current)) {
            EntityHelper::unsetFromEntity($current, $segments);
        } elseif (is_object($current)) {
            self::unsetFromObject($current, $segments);
        }
    }
}
