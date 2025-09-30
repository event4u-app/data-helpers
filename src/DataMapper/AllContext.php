<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * Context for beforeAll/afterAll hooks.
 */
use ArrayAccess;

/**
 * @implements ArrayAccess<(int | string), mixed>
 */
final class AllContext implements HookContext, ArrayAccess
{
    public function __construct(
        public string $mode,
        /** @var array<int|string,mixed> $mapping */
        public array $mapping,
        public mixed $source,
        public mixed $target,
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
        return null;
    }

    public function tgtPath(): ?string
    {
        return null;
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
            'mapping' => $this->mapping,
            'source' => $this->source,
            'target' => $this->target,
        ];
    }
}
