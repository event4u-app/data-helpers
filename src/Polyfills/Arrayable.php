<?php

declare(strict_types=1);

namespace Illuminate\Contracts\Support;

if (!interface_exists(Arrayable::class)) {
    /**
     * Polyfill for Laravel Arrayable interface when illuminate/support is not installed.
     *
     * @template TKey of array-key
     * @template TValue
     */
    interface Arrayable
    {
        /**
         * Get the instance as an array.
         *
         * @return array<TKey, TValue>
         */
        public function toArray(): array;
    }
}
