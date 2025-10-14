<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

/**
 * Handles wildcard normalization and iteration.
 */
class WildcardHandler
{
    /**
     * Normalize wildcard array results from DataAccessor.
     *
     * Picks the first numeric segment found in the key as the index.
     *
     * @param array<int|string,mixed> $array
     * @return array<int|string,mixed>
     */
    public static function normalizeWildcardArray(array $array): array
    {
        // Single pass optimization: check and process in one loop
        $hasDotPathKeys = false;
        $normalized = [];
        $hasMultipleWildcards = false;

        // First pass: detect dot-path keys and check for multiple wildcards
        foreach ($array as $key => $value) {
            if (!is_string($key) || !str_contains($key, '.')) {
                $normalized[$key] = $value;
                continue;
            }

            $hasDotPathKeys = true;

            // Split the key into segments (cache for reuse)
            $segments = explode('.', $key);
            $numericCount = 0;

            foreach ($segments as $seg) {
                if (is_numeric($seg)) {
                    $numericCount++;
                    if (1 < $numericCount) {
                        $hasMultipleWildcards = true;
                        break 2; // Break both loops
                    }
                }
            }

            // Free memory: unset segments array after use
            unset($segments);
        }

        // If no dot-path keys, return as-is
        if (!$hasDotPathKeys) {
            // Free memory: normalized not needed
            unset($normalized);

            return $array;
        }

        // For multi-wildcard paths, keep the full dot-path keys to avoid collisions
        if ($hasMultipleWildcards) {
            // Free memory: normalized not needed
            unset($normalized);

            return $array;
        }

        // Free memory: normalized not needed for single wildcard processing
        unset($normalized);

        // For single wildcard, extract the numeric index
        $result = [];
        foreach ($array as $key => $value) {
            if (!is_string($key) || !str_contains($key, '.')) {
                $result[$key] = $value;
                continue;
            }

            // Find the first numeric segment
            $segments = explode('.', $key);
            foreach ($segments as $segment) {
                if (is_numeric($segment)) {
                    $result[(int)$segment] = $value;
                    break;
                }
            }

            // Free memory: unset segments array after use
            unset($segments);
        }

        return $result;
    }

    /**
     * Iterate over wildcard items with optional null skipping and reindexing.
     *
     * @param array<int|string, mixed> $items
     * @param null|callable(int|string, string): void $onSkip optional callback invoked when an item is skipped
     *        (reason is either 'null' or 'skip')
     * @param callable(int|string, mixed): bool $onItem Should return true when an item was written/accepted,
     *        false when skipped (e.g. by beforeWrite or postTransform returning null).
     */
    public static function iterateWildcardItems(
        array $items,
        bool $skipNull,
        bool $reindex,
        ?callable $onSkip,
        callable $onItem
    ): void {
        $nextIndex = 0;
        foreach ($items as $originalIndex => $value) {
            // Skip null values if requested
            if ($skipNull && null === $value) {
                if (null !== $onSkip) {
                    $onSkip($originalIndex, 'null');
                }

                continue;
            }

            // Invoke the item callback; if it returns false, skip this item
            $accepted = $onItem($reindex ? $nextIndex : $originalIndex, $value);
            if (!$accepted) {
                if (null !== $onSkip) {
                    $onSkip($originalIndex, 'skip');
                }

                continue;
            }

            // Only increment nextIndex if reindexing and item was accepted
            if ($reindex) {
                $nextIndex++;
            }
        }
    }
}
