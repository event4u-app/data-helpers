<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

/**
 * Interface for Data Transfer Objects.
 *
 * Defines the contract for immutable DTOs that can be converted
 * to arrays and serialized to JSON.
 */
interface DTOInterface
{
    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Create a DTO instance from an array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static;
}
