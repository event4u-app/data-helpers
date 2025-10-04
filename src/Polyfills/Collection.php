<?php

declare(strict_types=1);

namespace Illuminate\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

if (!class_exists(Collection::class)) {
    /**
     * Polyfill for Laravel Collection when illuminate/support is not installed.
     * Provides minimal functionality needed by laravel-data-helpers.
     */
    class Collection implements ArrayAccess, Countable, IteratorAggregate
    {
        /** @var array<int|string, mixed> */
        protected array $items = [];

        /** @param array<int|string, mixed> $items */
        public function __construct(array $items = [])
        {
            $this->items = $items;
        }

        /**
         * Get all items.
         *
         * @return array<int|string, mixed>
         */
        public function all(): array
        {
            return $this->items;
        }

        /** Check if key exists. */
        public function has(int|string $key): bool
        {
            return array_key_exists($key, $this->items);
        }

        /** Get item by key. */
        public function get(int|string $key, mixed $default = null): mixed
        {
            return $this->items[$key] ?? $default;
        }

        /**
         * Convert to array.
         *
         * @return array<int|string, mixed>
         */
        public function toArray(): array
        {
            return $this->items;
        }

        // ArrayAccess implementation
        public function offsetExists(mixed $offset): bool
        {
            return isset($this->items[$offset]);
        }

        public function offsetGet(mixed $offset): mixed
        {
            return $this->items[$offset] ?? null;
        }

        public function offsetSet(mixed $offset, mixed $value): void
        {
            if (null === $offset) {
                $this->items[] = $value;
            } else {
                $this->items[$offset] = $value;
            }
        }

        public function offsetUnset(mixed $offset): void
        {
            unset($this->items[$offset]);
        }

        // Countable implementation
        public function count(): int
        {
            return count($this->items);
        }

        // IteratorAggregate implementation
        public function getIterator(): Traversable
        {
            return new ArrayIterator($this->items);
        }
    }
}
