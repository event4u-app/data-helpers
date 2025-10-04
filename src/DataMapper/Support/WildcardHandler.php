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
        // Check if we have dot-path keys (e.g., 'users.0.email')
        $hasDotPathKeys = false;
        foreach (array_keys($array) as $key) {
            if (is_string($key) && str_contains($key, '.')) {
                $hasDotPathKeys = true;

                break;
            }
        }

        // If no dot-path keys, return as-is
        if (!$hasDotPathKeys) {
            return $array;
        }

        // Extract numeric indices from dot-path keys
        // For single wildcard: 'users.0.email' -> index 0
        // For multiple wildcards: keep the full dot-path key to avoid collisions
        $normalized = [];
        $hasMultipleWildcards = false;

        foreach ($array as $key => $value) {
            if (!is_string($key) || !str_contains($key, '.')) {
                $normalized[$key] = $value;

                continue;
            }

            // Split the key into segments
            $segments = explode('.', $key);
            $numericSegments = array_filter($segments, fn($seg): bool => is_numeric($seg));

            // If we have multiple numeric segments, it's a multi-wildcard path
            if (count($numericSegments) > 1) {
                $hasMultipleWildcards = true;

                break;
            }
        }

        // For multi-wildcard paths, keep the full dot-path keys to avoid collisions
        if ($hasMultipleWildcards) {
            return $array;
        }

        // For single wildcard, extract the numeric index
        foreach ($array as $key => $value) {
            if (!is_string($key) || !str_contains($key, '.')) {
                $normalized[$key] = $value;

                continue;
            }

            // Find the first numeric segment
            $segments = explode('.', $key);
            foreach ($segments as $segment) {
                if (is_numeric($segment)) {
                    $normalized[(int)$segment] = $value;

                    break;
                }
            }
        }

        return $normalized;
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
