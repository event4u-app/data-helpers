<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Contracts;

/**
 * Interface for conditional property attributes.
 *
 * Conditional properties are only included in toArray()/toJson() output
 * when certain conditions are met (e.g., user has permission, context value matches, etc.).
 */
interface ConditionalProperty
{
    /**
     * Check if the property should be included in the output.
     *
     * @param mixed $value Property value
     * @param object $dto Dto instance
     * @param array<string, mixed> $context Context data passed to toArray()/toJson()
     * @return bool True if property should be included, false otherwise
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool;
}
