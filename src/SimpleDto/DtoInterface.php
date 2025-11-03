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
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public function toArray(array $context = []): array;

    /**
     * Create a Dto instance from an array.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, mixed>|null $filters Optional property filters
     * @param array<int, mixed>|null $pipeline Optional pipeline filters
     */
    public static function fromArray(
        array $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static;
}
