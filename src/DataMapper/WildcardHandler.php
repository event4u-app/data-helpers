<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

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
        // If dot-path keys are present, flatten to a simple list preserving order.
        // This avoids collisions when multiple wildcards are used in a single path
        // (e.g., departments.*.users.*.email), where collapsing to a single numeric
        // index would overwrite values.
        foreach ($array as $key => $_) {
            if (is_string($key) && str_contains($key, '.')) {
                $list = [];
                foreach ($array as $value) {
                    $list[] = $value;
                }

                return $list;
            }
        }

        return $array;
    }

    /**
     * Iterate over wildcard items with optional null skipping and reindexing.
     *
     * @param null|callable(int $skipNull, string $reason): void $onSkip optional callback invoked when an item is skipped
     *        (reason is either 'null' or 'skip')
     * @param callable(int $skipNull, mixed $value): bool $onItem Should return true when an item was written/accepted,
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
                if ($onSkip) {
                    $onSkip($originalIndex, 'null');
                }

                continue;
            }

            // Invoke the item callback; if it returns false, skip this item
            $accepted = $onItem($reindex ? $nextIndex : $originalIndex, $value);
            if (!$accepted) {
                if ($onSkip) {
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

