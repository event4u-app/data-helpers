<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\DataMapper\AllContext;
use event4u\DataHelpers\DataMapper\EntryContext;
use event4u\DataHelpers\DataMapper\HookContext;
use event4u\DataHelpers\DataMapper\Mode;
use event4u\DataHelpers\DataMapper\PairContext;
use event4u\DataHelpers\DataMapper\WriteContext;

/**
 * Helper to build and merge DataMapper hook definitions using the enum safely.
 */
final class Hooks
{
    /** @var array<string, mixed> */
    private array $hooks = [];

    private function __construct() {}

    /** Start a fluent hooks builder. */
    public static function make(): self
    {
        return new self();
    }

    /** Register a hook payload under the given name. */
    public function on(DataMapperHook|string $name, mixed $payload): self
    {
        $key = $name instanceof DataMapperHook ? $name->value : $name;
        $this->hooks[$key] = is_array($payload) ? self::normalizeArrayKeys($payload) : $payload;

        return $this;
    }

    /**
     * Convenience: register a filtered hook for a specific source path prefix (src:...)
     *
     * @phpstan-param callable(array<string, mixed>):((mixed | bool))|callable(HookContext):((mixed | bool))|callable(AllContext):((mixed | bool))|callable(EntryContext):((mixed | bool))|callable(PairContext):((mixed | bool))|callable(WriteContext):((mixed | bool))|callable(mixed, array<string, mixed>):mixed|callable(mixed, HookContext):mixed|callable(mixed, PairContext):mixed|callable(mixed, WriteContext):mixed $callback
     */
    public function onForSrc(DataMapperHook|string $name, string $srcPrefix, callable $callback): self
    {
        $key = $name instanceof DataMapperHook ? $name->value : $name;
        $this->ensureHookBucketArray($key);

        /** @var array<int|string, mixed> $bucket */
        $bucket = is_array($this->hooks[$key]) ? $this->hooks[$key] : [];
        $bucket['src:' . $srcPrefix] = $callback;
        $this->hooks[$key] = $bucket;

        return $this;
    }

    /**
     * Convenience: register a filtered hook for a specific target path prefix (tgt:...)
     *
     * @phpstan-param callable(array<string, mixed>):((mixed | bool))|callable(HookContext):((mixed | bool))|callable(AllContext):((mixed | bool))|callable(EntryContext):((mixed | bool))|callable(PairContext):((mixed | bool))|callable(WriteContext):((mixed | bool))|callable(mixed, array<string, mixed>):mixed|callable(mixed, HookContext):mixed|callable(mixed, PairContext):mixed|callable(mixed, WriteContext):mixed $callback
     */
    public function onForTgt(DataMapperHook|string $name, string $targetPrefix, callable $callback): self
    {
        $key = $name instanceof DataMapperHook ? $name->value : $name;
        $this->ensureHookBucketArray($key);

        /** @var array<int|string, mixed> $bucket */
        $bucket = is_array($this->hooks[$key]) ? $this->hooks[$key] : [];
        $bucket['tgt:' . $targetPrefix] = $callback;
        $this->hooks[$key] = $bucket;

        return $this;
    }

    /**
     * Convenience: register a filtered hook for a specific mapping mode (mode: simple|structured)
     *
     * @phpstan-param callable(array<string, mixed>):((mixed | bool))|callable(HookContext):((mixed | bool))|callable(AllContext):((mixed | bool))|callable(EntryContext):((mixed | bool))|callable(PairContext):((mixed | bool))|callable(WriteContext):((mixed | bool))|callable(mixed, array<string, mixed>):mixed|callable(mixed, HookContext):mixed|callable(mixed, PairContext):mixed|callable(mixed, WriteContext):mixed $callback
     */
    public function onForMode(DataMapperHook|string $name, string $mode, callable $callback): self
    {
        $mode = in_array(
            $mode,
            ['simple', 'structured', 'structured-assoc', 'structured-pairs'],
            true
        ) ? $mode : 'simple';
        $key = $name instanceof DataMapperHook ? $name->value : $name;
        $this->ensureHookBucketArray($key);

        /** @var array<int|string, mixed> $bucket */
        $bucket = is_array($this->hooks[$key]) ? $this->hooks[$key] : [];
        $bucket['mode:' . $mode] = $callback;
        $this->hooks[$key] = $bucket;

        return $this;
    }

    /**
     * Convenience: register a filtered hook for a specific mapping mode via enum
     *
     * @phpstan-param callable(array<string, mixed>):((mixed | bool))|callable(HookContext):((mixed | bool))|callable(AllContext):((mixed | bool))|callable(EntryContext):((mixed | bool))|callable(PairContext):((mixed | bool))|callable(WriteContext):((mixed | bool))|callable(mixed, array<string, mixed>):mixed|callable(mixed, HookContext):mixed|callable(mixed, PairContext):mixed|callable(mixed, WriteContext):mixed $callback
     */
    public function onForModeEnum(DataMapperHook|string $name, Mode $mode, callable $callback): self
    {
        return $this->onForMode($name, $mode->value, $callback);
    }

    /**
     * Convenience: register a hook that fires when EITHER srcPath OR tgtPath matches a prefix pattern.
     * Supports optional trailing '*' wildcard. Does not double-invoke.
     *
     * Examples:
     *  - onForPrefix(PreTransform, 'user.address.*', fn($v, PairContext $ctx) => ...)
     *  - onForPrefix(BeforeWrite, 'profile.', fn($v, WriteContext $ctx) => ...)
     *
     * Works for pair/write-level hooks where src/tgt are available. For hooks without paths
     * (beforeAll/afterAll/beforeEntry/afterEntry), the predicate will never match and the callback is skipped.
     *
     * @phpstan-param callable(array<string, mixed>):((mixed | bool))|callable(HookContext):((mixed | bool))|callable(AllContext):((mixed | bool))|callable(EntryContext):((mixed | bool))|callable(PairContext):((mixed | bool))|callable(WriteContext):((mixed | bool))|callable(mixed, array<string, mixed>):mixed|callable(mixed, HookContext):mixed|callable(mixed, PairContext):mixed|callable(mixed, WriteContext):mixed $callback
     */
    public function onForPrefix(DataMapperHook|string $name, string $prefix, callable $callback): self
    {
        $key = $name instanceof DataMapperHook ? $name->value : $name;
        $this->ensureHookBucketArray($key);

        /** @var array<int|string, mixed> $bucket */
        $bucket = is_array($this->hooks[$key]) ? $this->hooks[$key] : [];

        $wrapper = function (mixed ...$arguments) use ($prefix, $callback) {
            $context = null;
            foreach ($arguments as $argument) {
                if ($argument instanceof HookContext) {
                    $context = $argument;

                    break;
                }
                if (is_array($argument) && (array_key_exists('srcPath', $argument) || array_key_exists(
                            'tgtPath',
                            $argument
                        ))) {
                    $context = $argument;

                    break;
                }
            }

            $matchesPattern = function (?string $value, string $pattern): bool {
                if (null === $value) {
                    return false;
                }
                if ('*' === $pattern) {
                    return true;
                }
                if (str_ends_with($pattern, '*')) {
                    $prefixOnly = substr($pattern, 0, -1);

                    return str_starts_with($value, $prefixOnly);
                }

                return $value === $pattern || str_starts_with($value, $pattern);
            };

            $sourcePath = null;
            $targetPath = null;
            if ($context instanceof HookContext) {
                $sourcePath = $context->srcPath();
                $targetPath = $context->tgtPath();
            } elseif (is_array($context)) {
                $sourcePath = $context['srcPath'] ?? null;
                $targetPath = $context['tgtPath'] ?? null;
            }

            if ($matchesPattern($sourcePath, $prefix) || $matchesPattern($targetPath, $prefix)) {
                return $callback(...$arguments);
            }

            // Pass-through when predicate does not match
            $argumentCount = count($arguments);
            if (1 === $argumentCount) {
                // non-value hook -> no effect
                return null;
            }

            // afterWrite ($target, $context, $written)
            return $arguments[0];
        };

        // Append as unkeyed callback so it executes once with internal predicate
        $bucket[] = $wrapper;
        $this->hooks[$key] = $bucket;

        return $this;
    }

    /**
     * Ensure the bucket for the given hook name is an array.
     * If it currently holds a single callable, wrap it into a list [callable].
     */
    private function ensureHookBucketArray(string $key): void
    {
        if (!isset($this->hooks[$key])) {
            $this->hooks[$key] = [];

            return;
        }
        if (is_callable($this->hooks[$key])) {
            $this->hooks[$key] = [
                0 => $this->hooks[$key],
            ];
        } elseif (!is_array($this->hooks[$key])) {
            $this->hooks[$key] = [];
        }
    }

    /**
     * Register multiple hooks from list of pairs [name, payload].
     *
     * @param array<int, array{0: DataMapperHook|string, 1: mixed}> $pairs
     */
    public function onMany(array $pairs): self
    {
        foreach ($pairs as $definition) {
            if (is_array($definition) && array_key_exists(0, $definition) && array_key_exists(1, $definition)) {
                $this->on($definition[0], $definition[1]);
            }
        }

        return $this;
    }

    /**
     * Merge additional hook sets into the builder (later overrides earlier).
     *
     * @param array<int|string, mixed> ...$sets
     */
    public function mergeIn(array ...$sets): self
    {
        $this->hooks = self::merge($this->hooks, ...$sets);

        return $this;
    }

    /**
     * Build the array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->hooks;
    }

    /**
     * Build hooks from either an associative array (string keys) or a list of pairs
     * [DataMapperHook|string, mixed]. Nested arrays are normalized recursively.
     *
     * @param array<int|string, mixed> $hooks
     * @return array<string, mixed>
     */
    public static function build(array $hooks): array
    {
        $normalized = [];
        foreach ($hooks as $key => $value) {
            if (is_int($key)) {
                if (is_array($value) && array_key_exists(0, $value) && array_key_exists(1, $value)) {
                    $name = $value[0];
                    $payload = $value[1];
                    $hookName = $name instanceof DataMapperHook ? $name->value : (string)$name;
                    $normalized[$hookName] = is_array($payload) ? self::normalizeArrayKeys($payload) : $payload;
                }

                continue;
            }
            $normalized[$key] = is_array($value) ? self::normalizeArrayKeys($value) : $value;
        }

        return $normalized;
    }

    /**
     * Merge multiple hook sets. Later sets override earlier ones; nested arrays are merged shallowly.
     *
     * @param array<int|string, mixed> ...$sets
     * @return array<string, mixed>
     */
    public static function merge(array ...$sets): array
    {
        $merged = [];
        foreach ($sets as $set) {
            $normalized = self::build($set);
            foreach ($normalized as $key => $value) {
                if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                    $merged[$key] = array_merge($merged[$key], $value);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Recursively convert enum keys to strings in nested arrays.
     *
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    private static function normalizeArrayKeys(array $array): array
    {
        $normalized = [];
        foreach ($array as $key => $value) {
            $normalizedKey = is_int($key) ? $key : $key;
            if (is_array($value)) {
                $value = self::normalizeArrayKeys($value);
            }
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }
}
