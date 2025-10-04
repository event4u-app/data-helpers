<?php

declare(strict_types=1);

namespace Illuminate\Database\Eloquent;

use ArrayAccess;

if (!class_exists(Model::class)) {
    /**
     * Polyfill stub for Laravel Eloquent Model when illuminate/database is not installed.
     * This is just a marker class - real Model functionality requires illuminate/database.
     *
     * Note: This polyfill provides minimal functionality for type checking only.
     * If you need full Eloquent Model support, install illuminate/database.
     */
    abstract class Model implements ArrayAccess
    {
        /** @var array<string, mixed> */
        protected array $attributes = [];

        /**
         * Get all attributes.
         *
         * @return array<string, mixed>
         */
        public function getAttributes(): array
        {
            return $this->attributes;
        }

        /** Get an attribute. */
        public function getAttribute(string $key): mixed
        {
            return $this->attributes[$key] ?? null;
        }

        /** Set an attribute. */
        public function setAttribute(string $key, mixed $value): void
        {
            $this->attributes[$key] = $value;
        }

        /**
         * Convert to array.
         *
         * @return array<string, mixed>
         */
        public function toArray(): array
        {
            return $this->attributes;
        }

        // ArrayAccess implementation
        public function offsetExists(mixed $offset): bool
        {
            return isset($this->attributes[$offset]);
        }

        public function offsetGet(mixed $offset): mixed
        {
            return $this->getAttribute($offset);
        }

        public function offsetSet(mixed $offset, mixed $value): void
        {
            $this->setAttribute($offset, $value);
        }

        public function offsetUnset(mixed $offset): void
        {
            unset($this->attributes[$offset]);
        }
    }
}
