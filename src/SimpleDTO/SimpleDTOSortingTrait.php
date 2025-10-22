<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

/**
 * Trait for sorting array output.
 *
 * This trait provides methods to configure and apply sorting to toArray() and jsonSerialize() output.
 * Sorting can be enabled/disabled, configured for direction (ASC/DESC), and can be applied recursively.
 *
 * Example:
 *   $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
 *
 *   // Enable sorting
 *   $sorted = $user->sorted();
 *   $array = $sorted->toArray(); // Keys sorted alphabetically
 *
 *   // Sort descending
 *   $sorted = $user->sorted('desc');
 *   $array = $sorted->toArray();
 *
 *   // Enable nested sorting
 *   $sorted = $user->sorted()->withNestedSort();
 *   $array = $sorted->toArray();
 *
 *   // Custom sort callback
 *   $sorted = $user->sortedBy(fn($a, $b) => strcmp($b, $a));
 *   $array = $sorted->toArray();
 */
trait SimpleDTOSortingTrait
{
    /** @var bool Whether sorting is enabled */
    private bool $sortingEnabled = false;

    /** @var string Sort direction: 'asc' or 'desc' */
    private string $sortDirection = 'asc';

    /** @var bool Whether to sort nested arrays */
    private bool $nestedSort = false;

    /** @var (callable(mixed, mixed): int)|null Custom sort callback */
    private $sortCallback = null;

    /**
     * Enable sorting with optional direction.
     *
     * @param string $direction Sort direction: 'asc' or 'desc' (default: 'asc')
     */
    public function sorted(string $direction = 'asc'): static
    {
        $clone = clone $this;
        $clone->sortingEnabled = true;
        $clone->sortDirection = strtolower($direction);

        return $clone;
    }

    /** Disable sorting. */
    public function unsorted(): static
    {
        $clone = clone $this;
        $clone->sortingEnabled = false;

        return $clone;
    }

    /**
     * Enable nested sorting.
     *
     * When enabled, nested arrays will also be sorted recursively.
     *
     * @param bool $enabled Whether to enable nested sorting (default: true)
     */
    public function withNestedSort(bool $enabled = true): static
    {
        $clone = clone $this;
        $clone->nestedSort = $enabled;

        return $clone;
    }

    /**
     * Sort using a custom callback.
     *
     * The callback receives two keys and should return:
     * - negative value if first key should come before second
     * - zero if keys are equal
     * - positive value if first key should come after second
     *
     * Example:
     *   $sorted = $dto->sortedBy(fn($a, $b) => strcmp($b, $a)); // Reverse alphabetical
     *
     * @param callable(mixed, mixed): int $callback Sort callback (receives two keys)
     */
    public function sortedBy(callable $callback): static
    {
        $clone = clone $this;
        $clone->sortingEnabled = true;
        $clone->sortCallback = $callback;

        return $clone;
    }

    /**
     * Apply sorting to an array.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function applySorting(array $data): array
    {
        if (!$this->sortingEnabled) {
            return $data;
        }

        // Sort the array
        $data = $this->sortArray($data);

        // Apply nested sorting if enabled
        if ($this->nestedSort) {
            return $this->sortNestedArrays($data);
        }

        return $data;
    }

    /**
     * Sort an array by keys.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sortArray(array $data): array
    {
        // Use custom callback if provided
        if (null !== $this->sortCallback) {
            uksort($data, $this->sortCallback);

            return $data;
        }

        // Sort by keys
        if ('desc' === $this->sortDirection) {
            krsort($data, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            ksort($data, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $data;
    }

    /**
     * Sort nested arrays recursively.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sortNestedArrays(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Sort the nested array
                /** @var array<string, mixed> $nestedArray */
                $nestedArray = $value;
                $nestedArray = $this->sortArray($nestedArray);

                // Recursively sort deeper levels
                $nestedArray = $this->sortNestedArrays($nestedArray);

                $data[$key] = $nestedArray;
            }
        }

        return $data;
    }
}
