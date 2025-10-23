<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Serializers;

/**
 * YAML serializer for DTOs.
 *
 * Converts DTO data to YAML format.
 * This is a simple implementation that doesn't require the symfony/yaml package.
 *
 * Example:
 *   $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
 *   $yaml = $user->toYaml();
 *   // Result: name: John\nage: 30
 */
class YamlSerializer implements SerializerInterface
{
    public function __construct(
        private readonly int $indent = 2,
    ) {}

    /**
     * Serialize data to YAML.
     *
     * @param array<string, mixed> $data
     */
    public function serialize(array $data): string
    {
        return $this->arrayToYaml($data, 0);
    }

    /**
     * Convert array to YAML string.
     *
     * @param array<string, mixed> $data
     */
    private function arrayToYaml(array $data, int $level): string
    {
        $yaml = '';
        $indent = str_repeat(' ', $level * $this->indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                // Check if it's a sequential array
                if ($this->isSequentialArray($value)) {
                    $yaml .= $indent . $key . ':' . PHP_EOL;
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            /** @var array<string, mixed> $item */
                            $yaml .= $indent . str_repeat(' ', $this->indent) . '-' . PHP_EOL;
                            $yaml .= $this->arrayToYaml($item, $level + 2);
                        } else {
                            $yaml .= $indent . str_repeat(' ', $this->indent) . '- ' . $this->formatValue(
                                $item
                            ) . PHP_EOL;
                        }
                    }
                } else {
                    $yaml .= $indent . $key . ':' . PHP_EOL;
                    $yaml .= $this->arrayToYaml($value, $level + 1);
                }
            } else {
                $yaml .= $indent . $key . ': ' . $this->formatValue($value) . PHP_EOL;
            }
        }

        return $yaml;
    }

    /**
     * Check if array is sequential (0, 1, 2, ...).
     *
     * @param array<mixed> $array
     */
    private function isSequentialArray(array $array): bool
    {
        if ([] === $array) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /** Format a value for YAML output. */
    private function formatValue(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            // Quote strings that contain special characters
            if ($this->needsQuoting($value)) {
                return '"' . addslashes($value) . '"';
            }

            return $value;
        }

        return (string)$value;
    }

    /** Check if a string needs quoting in YAML. */
    private function needsQuoting(string $value): bool
    {
        // Quote if contains special YAML characters
        return preg_match('/[:\[\]{},&*#?|\-<>=!%@`]/', $value) === 1
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ')
            || str_contains($value, "\n");
    }

    public function getContentType(): string
    {
        return 'application/x-yaml';
    }
}
