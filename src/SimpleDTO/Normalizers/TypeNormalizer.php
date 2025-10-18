<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Normalizers;

/**
 * Normalizer that coerces values to their expected types.
 *
 * This normalizer ensures that values are of the correct type,
 * converting them if necessary.
 *
 * Example:
 *   - "123" -> 123 (string to int)
 *   - "true" -> true (string to bool)
 *   - 1 -> true (int to bool)
 */
class TypeNormalizer implements NormalizerInterface
{
    /**
     * @param array<string, string> $types Map of field names to expected types
     */
    public function __construct(
        private readonly array $types = []
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        foreach ($this->types as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }

            $data[$field] = $this->coerceType($data[$field], $type);
        }

        return $data;
    }

    /**
     * Coerce a value to the specified type.
     */
    private function coerceType(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => $this->toInt($value),
            'float', 'double' => $this->toFloat($value),
            'bool', 'boolean' => $this->toBool($value),
            'string' => $this->toString($value),
            'array' => $this->toArray($value),
            default => $value,
        };
    }

    private function toInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return (int) $value;
    }

    private function toFloat(mixed $value): float
    {
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        return (float) $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_string($value)) {
            $lower = strtolower($value);

            return match ($lower) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off', '' => false,
                default => (bool) $value,
            };
        }

        return (bool) $value;
    }

    private function toString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        return (string) $value;
    }

    private function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [$value];
        }

        return [$value];
    }
}

