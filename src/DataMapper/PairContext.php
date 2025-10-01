<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * Context for beforePair/afterPair and pre/post transform hooks.
 */

use ArrayAccess;

/**
 * @implements ArrayAccess<(int | string), mixed>
 */
class PairContext implements HookContext, ArrayAccess
{
    public function __construct(
        public string $mode,
        public int $pairIndex,
        public string $srcPath,
        public string $tgtPath,
        public mixed $source,
        public mixed $target,
        public int|string|null $wildcardIndex = null,
    ) {}

    public function mode(): string
    {
        return $this->mode;
    }

    public function modeEnum(): Mode
    {
        return Mode::from($this->mode);
    }

    public function srcPath(): ?string
    {
        return $this->srcPath;
    }

    public function tgtPath(): ?string
    {
        return $this->tgtPath;
    }

    /** ArrayAccess compatibility for legacy array-typed callbacks. */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_int($offset) && !is_string($offset)) {
            return false;
        }

        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_int($offset) && !is_string($offset)) {
            return null;
        } $a = $this->toArray();

        return $a[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    { // immutable for hooks
    }

    public function offsetUnset(mixed $offset): void
    { // immutable for hooks
    }

    /**
     * Represent context as an associative array.
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'pairIndex' => $this->pairIndex,
            'srcPath' => $this->srcPath,
            'tgtPath' => $this->tgtPath,
            'source' => $this->source,
            'target' => $this->target,
            'wildcardIndex' => $this->wildcardIndex,
        ];
    }
}
