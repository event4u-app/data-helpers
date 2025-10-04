<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Handles hook invocation and normalization.
 */
class HookInvoker
{
    /**
     * Normalize hooks array: convert enum keys to string names and handle list-of-pairs format.
     *
     * - Accept list of pairs [DataMapperHook|string, mixed] and convert to assoc
     *
     * @param array<int|string, mixed> $hooks
     * @return array<string, mixed>
     */
    public static function normalizeHooks(array $hooks): array
    {
        $normalized = [];
        foreach ($hooks as $key => $value) {
            if (is_int($key) && is_array($value) && array_key_exists(0, $value) && array_key_exists(1, $value)) {
                $name = $value[0];
                $name = $name instanceof DataMapperHook ? $name->value : (string)$name;
                $normalized[$name] = $value[1];
            } else {
                $name = $key instanceof DataMapperHook ? $key->value : (string)$key;
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Merge two hook arrays (shallow merge, later overrides earlier).
     *
     * @param array<string,mixed> $base
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    public static function mergeHooks(array $base, array $override): array
    {
        foreach ($override as $k => $v) {
            if (isset($base[$k]) && is_array($base[$k]) && is_array($v)) {
                $base[$k] = array_merge($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }

        return $base;
    }

    /**
     * Invoke a hook by name with a context.
     * Returns the last non-null result from invoked hooks.
     * If any returns false, false is returned (caller may treat as cancel).
     *
     * @param array<string,mixed> $hooks
     */
    public static function invokeHooks(array $hooks, string $name, HookContext $context): mixed
    {
        if (!isset($hooks[$name])) {
            return null;
        }
        $hookPayload = $hooks[$name];
        $lastResult = null;

        // Single callable
        if (is_callable($hookPayload)) {
            return self::invokeCallback($hookPayload, $context);
        }

        // Array of callables with optional prefix filters
        if (is_array($hookPayload)) {
            foreach ($hookPayload as $filterKey => $callback) {
                if (!is_callable($callback)) {
                    continue;
                }

                // Check for prefix filters (src:, tgt:, mode:)
                if (is_string($filterKey)) {
                    $matchesFilter = true;

                    if (str_starts_with($filterKey, 'src:') && null !== $context->srcPath()) {
                        $pattern = substr($filterKey, 4);
                        $matchesFilter = self::matchPrefixPattern($context->srcPath(), $pattern);
                    } elseif (str_starts_with($filterKey, 'tgt:') && null !== $context->tgtPath()) {
                        $pattern = substr($filterKey, 4);
                        $matchesFilter = self::matchPrefixPattern($context->tgtPath(), $pattern);
                    } elseif (str_starts_with($filterKey, 'mode:')) {
                        $pattern = substr($filterKey, 5);
                        $matchesFilter = $context->mode() === $pattern;
                    }

                    if (!$matchesFilter) {
                        continue;
                    }
                }

                $result = self::invokeCallback($callback, $context);
                if (false === $result) {
                    return false;
                }
                if (null !== $result) {
                    $lastResult = $result;
                }
            }
        }

        return $lastResult;
    }

    /**
     * Invoke a value-transforming hook (preTransform, postTransform).
     * The callable signature should be: function(mixed $value, array|HookContext $context): mixed
     * When a list or associative list of hooks is provided, they are applied in order.
     *
     * @param array<string,mixed> $hooks
     */
    public static function invokeValueHook(array $hooks, string $name, HookContext $context, mixed $value): mixed
    {
        if (!isset($hooks[$name])) {
            return $value;
        }
        $hookPayload = $hooks[$name];

        // Single callable
        if (is_callable($hookPayload)) {
            return self::invokeValueCallback($hookPayload, $value, $context);
        }

        // Array of callables with optional prefix filters
        if (is_array($hookPayload)) {
            foreach ($hookPayload as $filterKey => $callback) {
                if (!is_callable($callback)) {
                    continue;
                }

                // Check for prefix filters (src:, tgt:, mode:)
                if (is_string($filterKey)) {
                    $matchesFilter = true;

                    if (str_starts_with($filterKey, 'src:') && null !== $context->srcPath()) {
                        $pattern = substr($filterKey, 4);
                        $matchesFilter = self::matchPrefixPattern($context->srcPath(), $pattern);
                    } elseif (str_starts_with($filterKey, 'tgt:') && null !== $context->tgtPath()) {
                        $pattern = substr($filterKey, 4);
                        $matchesFilter = self::matchPrefixPattern($context->tgtPath(), $pattern);
                    } elseif (str_starts_with($filterKey, 'mode:')) {
                        $pattern = substr($filterKey, 5);
                        $matchesFilter = $context->mode() === $pattern;
                    }

                    if (!$matchesFilter) {
                        continue;
                    }
                }

                $value = self::invokeValueCallback($callback, $value, $context);
            }
        }

        return $value;
    }

    /**
     * Invoke target-mutating hook like afterWrite. Expected signature:
     *   function(mixed $target, array|HookContext $context, mixed $writtenValue): mixed
     *
     * @param array<string,mixed> $hooks
     */
    public static function invokeTargetHook(
        array $hooks,
        string $name,
        HookContext $context,
        mixed $writtenValue,
        mixed $target,
    ): mixed {
        if (!isset($hooks[$name])) {
            return $target;
        }
        $hookPayload = $hooks[$name];

        // Support both single callable and array of callables
        $callables = is_array($hookPayload) && !is_callable($hookPayload) ? $hookPayload : [$hookPayload];

        foreach ($callables as $callable) {
            if (!is_callable($callable)) {
                continue;
            }

            // Invoke with target, context, and written value
            $target = self::invokeTargetCallback($callable, $target, $context, $writtenValue);
        }

        return $target;
    }

    /** Simple prefix matcher supporting optional trailing '*' wildcard. */
    public static function matchPrefixPattern(string $value, string $pattern): bool
    {
        if ('*' === $pattern) {
            return true;
        }
        if (str_ends_with($pattern, '*')) {
            $prefix = substr($pattern, 0, -1);

            return str_starts_with($value, $prefix);
        }

        return $value === $pattern || str_starts_with($value, $pattern);
    }

    /**
     * Invoke a callback with automatic context conversion (array vs object).
     * Uses reflection to detect if callback expects array or HookContext.
     */
    private static function invokeCallback(callable $callback, HookContext $context): mixed
    {
        return $callback($context);
    }

    /** Invoke a value-transforming callback with automatic context conversion. */
    private static function invokeValueCallback(callable $callback, mixed $value, HookContext $context): mixed
    {
        return $callback($value, $context);
    }

    /** Invoke a target-mutating callback with automatic context conversion. */
    private static function invokeTargetCallback(
        callable $callback,
        mixed $target,
        HookContext $context,
        mixed $writtenValue
    ): mixed {
        return $callback($target, $context, $writtenValue);
    }
}
