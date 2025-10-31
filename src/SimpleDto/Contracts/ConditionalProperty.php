<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Contracts;

/**
 * Interface for conditional property attributes.
 *
 * Attributes implementing this interface can control whether a property
 * should be included in toArray() and toJson() output.
 */
interface ConditionalProperty
{
    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context (e.g., user, request)
     * @return bool True if property should be included, false otherwise
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool;
}
