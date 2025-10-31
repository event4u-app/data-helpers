<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

/**
 * Interface for Data Transfer Objects.
 *
 * Defines the contract for immutable Dtos that can be converted
 * to arrays and serialized to JSON.
 */
interface DtoInterface
{
    /**
     * Convert the Dto to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Create a Dto instance from an array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static;
}
