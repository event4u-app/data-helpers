<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Transformers;

/**
 * Transformer that converts specified string values to lowercase.
 *
 * Converts string values to lowercase for specified fields.
 */
class LowerCaseTransformer implements TransformerInterface
{
    /**
     * @param array<int, string> $fields Fields to convert to lowercase (empty = all fields)
     */
    public function __construct(
        private array $fields = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transform(array $data): array
    {
        return $this->lowerCaseRecursive($data);
    }

    /**
     * Convert strings to lowercase recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function lowerCaseRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            // Only transform if no specific fields are set, or if this field is in the list
            $shouldTransform = empty($this->fields) || in_array($key, $this->fields, true);

            if ($shouldTransform && is_string($value)) {
                $data[$key] = strtolower($value);
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $data[$key] = $this->lowerCaseRecursive($value);
            }
        }

        return $data;
    }
}

