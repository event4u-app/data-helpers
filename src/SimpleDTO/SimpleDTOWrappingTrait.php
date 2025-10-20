<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

/**
 * Trait for wrapping DTO data in a custom key.
 *
 * This trait provides methods to wrap DTO data in a custom key when converting
 * to array or JSON. This is useful for API responses that require a specific
 * structure like {"data": {...}} or {"result": {...}}.
 *
 * Example:
 *   $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
 *
 *   // Wrap in 'data' key
 *   $wrapped = $user->wrap('data')->toArray();
 *   // Result: ['data' => ['name' => 'John', 'age' => 30]]
 *
 *   // Wrap in custom key
 *   $wrapped = $user->wrap('user')->toArray();
 *   // Result: ['user' => ['name' => 'John', 'age' => 30]]
 *
 *   // Unwrap
 *   $unwrapped = UserDTO::unwrap(['data' => ['name' => 'John', 'age' => 30]], 'data');
 *   // Result: ['name' => 'John', 'age' => 30]
 */
trait SimpleDTOWrappingTrait
{
    /** The key to wrap the DTO data in. */
    private ?string $wrapKey = null;

    /**
     * Wrap the DTO data in a custom key.
     *
     * @param string $key The key to wrap the data in
     */
    public function wrap(string $key): static
    {
        $clone = clone $this;
        $clone->wrapKey = $key;

        return $clone;
    }

    /** Get the wrap key. */
    public function getWrapKey(): ?string
    {
        return $this->wrapKey;
    }

    /** Check if the DTO is wrapped. */
    public function isWrapped(): bool
    {
        return null !== $this->wrapKey;
    }

    /**
     * Unwrap data from a custom key.
     *
     * @param array<string, mixed> $data The wrapped data
     * @param string $key The key to unwrap from
     * @return array<string, mixed> The unwrapped data
     */
    public static function unwrap(array $data, string $key): array
    {
        return $data[$key] ?? [];
    }

    /**
     * Apply wrapping to the data if a wrap key is set.
     *
     * @param array<string, mixed> $data The data to wrap
     * @return array<string, mixed> The wrapped or original data
     */
    protected function applyWrapping(array $data): array
    {
        if (null === $this->wrapKey) {
            return $data;
        }

        return [$this->wrapKey => $data];
    }
}

