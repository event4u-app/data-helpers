<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Converters;

use InvalidArgumentException;

/**
 * JSON converter for bidirectional conversion between arrays and JSON strings.
 *
 * Example:
 *   $converter = new JsonConverter();
 *   $array = $converter->toArray('{"name":"John","age":30}');
 *   $json = $converter->fromArray(['name' => 'John', 'age' => 30]);
 */
class JsonConverter implements ConverterInterface
{
    public function __construct(
        private readonly bool $prettyPrint = false,
        private readonly int $flags = 0,
    ) {}

    /**
     * Convert JSON string to array.
     *
     * @param string $data JSON string
     * @return array<string, mixed>
     * @throws InvalidArgumentException If JSON is invalid
     */
    public function toArray(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        $decoded = json_decode($data, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(
                'Invalid JSON: ' . json_last_error_msg()
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('JSON must decode to an array');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Convert array to JSON string.
     *
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): string
    {
        $flags = $this->flags;

        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $flags |= JSON_THROW_ON_ERROR;

        $json = json_encode($data, $flags);

        if (false === $json) {
            throw new InvalidArgumentException(
                'Failed to encode array to JSON: ' . json_last_error_msg()
            );
        }

        return $json;
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }
}
