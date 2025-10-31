<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Serializers;

/**
 * Interface for custom serializers.
 *
 * Serializers convert Dto data to different formats like XML, YAML, CSV, etc.
 */
interface SerializerInterface
{
    /**
     * Serialize data to a string.
     *
     * @param array<string, mixed> $data The data to serialize
     * @return string The serialized data
     */
    public function serialize(array $data): string;

    /**
     * Get the content type for this serializer.
     *
     * @return string The content type (e.g., 'application/xml', 'text/csv')
     */
    public function getContentType(): string;
}
