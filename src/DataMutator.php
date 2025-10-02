<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\Support\CollectionHelper;
use event4u\DataHelpers\Support\EntityHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

class DataMutator
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $refClassCache = [];

    /** @var array<class-string, array<string, null|ReflectionProperty>> */
    private static array $refPropCache = [];

    /** @return ReflectionClass<object> */
    private static function getRefClass(object $object): ReflectionClass
    {
        $cls = $object::class;

        return self::$refClassCache[$cls] ??= new ReflectionClass($object);
    }

    private static function getRefProperty(object $object, string $name): ?ReflectionProperty
    {
        $cls = $object::class;
        $map = self::$refPropCache[$cls] ?? null;
        if (null !== $map && array_key_exists($name, $map)) {
            return $map[$name];
        }
        $ref = self::getRefClass($object);
        if (!$ref->hasProperty($name)) {
            // cache negative lookup
            self::$refPropCache[$cls][$name] = null;

            return null;
        }
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        self::$refPropCache[$cls][$name] = $prop;

        return $prop;
    }

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
        return self::set($target, $pathOrValues, $value, merge: true);
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

            return CollectionHelper::fromArray($arr);
        }

        // Support for any entity/model type (Eloquent, Doctrine)
        if (EntityHelper::isEntity($target)) {
            EntityHelper::setAttribute($target, implode('.', $segments), $value);

            return $target;
        }

        // Fallback for Laravel-specific types
        if ($target instanceof Collection) {
            $arr = $target->all();
            self::setIntoArray($arr, $segments, $value, $merge);

            return new Collection($arr);
        }

        if ($target instanceof Model) {
            $target->setAttribute(implode('.', $segments), $value);

            return $target;
        }

        if ($target instanceof Arrayable) {
            $arr = $target->toArray();
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
     */
    private static function setIntoArray(array &$array, array $segments, mixed $value, bool $merge): void
    {
        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }

        if (DotPathHelper::isWildcard($segment)) {
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

        $ref = self::getRefClass($object);
        if (!$ref->hasProperty($segment) && [] === $segments) {
            $object->{$segment} = $value;

            return;
        }

        $prop = self::getRefProperty($object, $segment);
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
            } elseif ($current instanceof Collection) {
                $arr = $current->all();
                self::setIntoArray($arr, $segments, $value, $merge);
                $prop->setValue($object, new Collection($arr));

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

            if ($target instanceof Collection) {
                $arr = $target->all();
                self::unsetFromArray($arr, $segments);
                $target = new Collection($arr);

                continue;
            }

            if ($target instanceof Model) {
                self::unsetFromModel($target, $segments);

                continue;
            }

            if ($target instanceof Arrayable) {
                $arr = $target->toArray();
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

            self::forEachArrayItem($array, function(&$item, int|string $key) use ($segments): void {
                if (is_array($item)) {
                    self::unsetFromArray($item, $segments);
                } elseif ($item instanceof Model) {
                    self::unsetFromModel($item, $segments);
                } elseif ($item instanceof Collection) {
                    $arr = $item->all();
                    self::unsetFromArray($arr, $segments);
                    $item = new Collection($arr);
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
        } elseif ($array[$segment] instanceof Model) {
            self::unsetFromModel($array[$segment], $segments);
        } elseif ($array[$segment] instanceof Collection) {
            $arr = $array[$segment]->all();
            self::unsetFromArray($arr, $segments);
            $array[$segment] = new Collection($arr);
        } elseif (is_object($array[$segment])) {
            self::unsetFromObject($array[$segment], $segments);
        }
    }

    /**
     * Recursively unset from models.
     *
     * @param array<int, string> $segments
     */
    private static function unsetFromModel(Model $model, array $segments): void
    {
        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }

        if (DotPathHelper::isWildcard($segment)) {
            $attributes = $model->getAttributes();
            if ([] === $segments) {
                foreach (array_keys($attributes) as $key) {
                    $model->offsetUnset($key);
                }

                return;
            }

            foreach ($attributes as $key => $value) {
                if (is_array($value)) {
                    self::unsetFromArray($value, $segments);
                    $model->setAttribute($key, $value);
                } elseif ($value instanceof Model) {
                    self::unsetFromModel($value, $segments);
                } elseif ($value instanceof Collection) {
                    $arr = $value->all();
                    self::unsetFromArray($arr, $segments);
                    $model->setAttribute($key, new Collection($arr));
                }
            }

            return;
        }

        if ([] === $segments) {
            $model->offsetUnset($segment);

            return;
        }

        $value = $model->getAttribute($segment);
        if (is_array($value)) {
            self::unsetFromArray($value, $segments);
            $model->setAttribute($segment, $value);
        } elseif ($value instanceof Model) {
            self::unsetFromModel($value, $segments);
        } elseif ($value instanceof Collection) {
            $arr = $value->all();
            self::unsetFromArray($arr, $segments);
            $model->setAttribute($segment, new Collection($arr));
        }
    }

    /**
     * Recursively unset from generic objects.
     *
     * @param array<int, string> $segments
     */
    private static function unsetFromObject(object $object, array $segments): void
    {
        if ($object instanceof Model) {
            self::unsetFromModel($object, $segments);

            return;
        }

        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }
        $ref = self::getRefClass($object);

        if (!$ref->hasProperty($segment)) {
            return;
        }

        $prop = self::getRefProperty($object, $segment);
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
        } elseif ($current instanceof Collection) {
            $arr = $current->all();
            self::unsetFromArray($arr, $segments);
            $prop->setValue($object, new Collection($arr));
        } elseif ($current instanceof Model) {
            self::unsetFromModel($current, $segments);
        } elseif (is_object($current)) {
            self::unsetFromObject($current, $segments);
        }
    }
}
