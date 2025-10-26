<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Transformers;

/**
 * Transformer that converts specified string values to uppercase.
 *
 * Converts string values to uppercase for specified fields.
 */
class UpperCaseTransformer implements TransformerInterface
{
    /** @param array<int, string> $fields Fields to convert to uppercase (empty = all fields) */
    public function __construct(
        private readonly array $fields = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transform(array $data): array
    {
        return $this->upperCaseRecursive($data);
    }

    /**
     * Convert strings to uppercase recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function upperCaseRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            // Only transform if no specific fields are set, or if this field is in the list
            $shouldTransform = [] === $this->fields || in_array($key, $this->fields, true);

            if ($shouldTransform && is_string($value)) {
                $data[$key] = strtoupper($value);
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $data[$key] = $this->upperCaseRecursive($value);
            }
        }

        return $data;
    }
}
