<?php

declare(strict_types=1);

namespace Doctrine\Common\Collections;

if (!interface_exists(\Doctrine\Common\Collections\Collection::class)) {
    /**
     * Polyfill for Doctrine Collection interface when doctrine/collections is not installed.
     *
     * @template TKey of array-key
     * @template T
     */
    interface Collection extends \Countable, \IteratorAggregate, \ArrayAccess
    {
        /**
         * Gets all elements.
         *
         * @return array<TKey, T>
         */
        public function toArray(): array;

        /**
         * Checks whether the collection contains an element with the specified key/index.
         *
         * @param string|int $key
         */
        public function containsKey(mixed $key): bool;

        /**
         * Gets the element at the specified key/index.
         *
         * @param string|int $key
         * @return T|null
         */
        public function get(mixed $key): mixed;
    }
}

if (!class_exists(\Doctrine\Common\Collections\ArrayCollection::class)) {
    /**
     * Polyfill for Doctrine ArrayCollection when doctrine/collections is not installed.
     * Provides minimal functionality needed by laravel-data-helpers.
     *
     * @template TKey of array-key
     * @template T
     * @implements Collection<TKey, T>
     */
    class ArrayCollection implements Collection
    {
        /** @var array<TKey, T> */
        private array $elements;

        /**
         * @param array<TKey, T> $elements
         */
        public function __construct(array $elements = [])
        {
            $this->elements = $elements;
        }

        /**
         * @return array<TKey, T>
         */
        public function toArray(): array
        {
            return $this->elements;
        }

        public function containsKey(mixed $key): bool
        {
            return isset($this->elements[$key]) || array_key_exists($key, $this->elements);
        }

        public function get(mixed $key): mixed
        {
            return $this->elements[$key] ?? null;
        }

        // Countable
        public function count(): int
        {
            return count($this->elements);
        }

        // IteratorAggregate
        public function getIterator(): \Traversable
        {
            return new \ArrayIterator($this->elements);
        }

        // ArrayAccess
        public function offsetExists(mixed $offset): bool
        {
            return $this->containsKey($offset);
        }

        public function offsetGet(mixed $offset): mixed
        {
            return $this->get($offset);
        }

        public function offsetSet(mixed $offset, mixed $value): void
        {
            if (null === $offset) {
                $this->elements[] = $value;
            } else {
                $this->elements[$offset] = $value;
            }
        }

        public function offsetUnset(mixed $offset): void
        {
            unset($this->elements[$offset]);
        }
    }
}

