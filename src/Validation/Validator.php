<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Validation;

use event4u\DataHelpers\Exceptions\ValidationException;

/**
 * Framework-independent validator.
 *
 * Provides basic validation similar to Laravel, but without dependencies.
 *
 * Example:
 * ```php
 * $validator = new Validator($data, [
 *     'email' => ['required', 'email'],
 *     'name' => ['required', 'min:3', 'max:50'],
 *     'age' => ['integer', 'min:18'],
 * ]);
 *
 * if ($validator->fails()) {
 *     print_r($validator->errors());
 * } else {
 *     $validated = $validator->validated();
 * }
 * ```
 */
class Validator
{
    /** @var array<string, array<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    private bool $stopOnFirstFailure = false;

    /**
     * @param array<string, mixed> $data Data to validate
     * @param array<string, array<string>|string> $rules Validation rules
     * @param array<string, string> $messages Custom error messages
     * @param array<string, string> $attributes Custom attribute names
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $messages = [],
        private readonly array $attributes = [],
    ) {}

    /**
     * Run validation.
     *
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rules = is_array($fieldRules) ? $fieldRules : [$fieldRules];
            $value = $this->data[$field] ?? null;
            $fieldErrors = [];

            foreach ($rules as $rule) {
                $error = $this->validateRule($field, $value, $rule);
                if (null !== $error) {
                    $fieldErrors[] = $error;
                    if ($this->stopOnFirstFailure) {
                        break 2;
                    }
                }
            }

            if ([] !== $fieldErrors) {
                $this->errors[$field] = $fieldErrors;
            } else {
                $this->validated[$field] = $value;
            }
        }

        if ([] !== $this->errors) {
            // Use ValidationException for consistency
            throw ValidationException::withMessages($this->errors);
        }

        return $this->validated;
    }

    /** Check if validation fails. */
    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException) {
            return true;
        }
    }

    /** Check if validation passes. */
    public function passes(): bool
    {
        return !$this->fails();
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /** Set stop on first failure. */
    public function stopOnFirstFailure(bool $stop = true): self
    {
        $this->stopOnFirstFailure = $stop;
        return $this;
    }

    /** Validate a single rule. */
    private function validateRule(string $field, mixed $value, string $rule): ?string
    {
        // Parse rule and parameters
        [$ruleName, $parameters] = $this->parseRule($rule);

        // Get attribute name for error message
        $attribute = $this->attributes[$field] ?? $field;

        // Validate based on rule
        return match ($ruleName) {
            'required' => $this->validateRequired($attribute, $value),
            'email' => $this->validateEmail($attribute, $value),
            'string' => $this->validateString($attribute, $value),
            'integer', 'int' => $this->validateInteger($attribute, $value),
            'numeric' => $this->validateNumeric($attribute, $value),
            'boolean', 'bool' => $this->validateBoolean($attribute, $value),
            'array' => $this->validateArray($attribute, $value),
            'min' => $this->validateMin($attribute, $value, (float)($parameters[0] ?? 0)),
            'max' => $this->validateMax($attribute, $value, (float)($parameters[0] ?? 0)),
            'between' => $this->validateBetween(
                $attribute,
                $value,
                (float)($parameters[0] ?? 0),
                (float)($parameters[1] ?? 0)
            ),
            'in' => $this->validateIn($attribute, $value, $parameters),
            'not_in' => $this->validateNotIn($attribute, $value, $parameters),
            'regex' => $this->validateRegex($attribute, $value, $parameters[0] ?? ''),
            'url' => $this->validateUrl($attribute, $value),
            'uuid' => $this->validateUuid($attribute, $value),
            'confirmed' => $this->validateConfirmed($field, $attribute, $value),
            'size' => $this->validateSize($attribute, $value, (int)($parameters[0] ?? 0)),
            'same' => $this->validateSame($field, $attribute, $value, $parameters[0] ?? ''),
            'different' => $this->validateDifferent($field, $attribute, $value, $parameters[0] ?? ''),
            'starts_with' => $this->validateStartsWith($attribute, $value, $parameters),
            'ends_with' => $this->validateEndsWith($attribute, $value, $parameters),
            'ip' => $this->validateIp($attribute, $value),
            'ipv4' => $this->validateIpv4($attribute, $value),
            'ipv6' => $this->validateIpv6($attribute, $value),
            'json' => $this->validateJson($attribute, $value),
            default => null, // Unknown rule, skip
        };
    }

    /**
     * Parse rule string into name and parameters.
     *
     * @return array{string, array<string>}
     */
    private function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $params] = explode(':', $rule, 2);
        return [$name, explode(',', $params)];
    }

    /** Get custom error message or default. */
    private function getMessage(string $field, string $rule, string $default): string
    {
        return $this->messages[sprintf('%s.%s', $field, $rule)] ?? $this->messages[$rule] ?? $default;
    }

    // Validation methods
    private function validateRequired(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value || (is_array($value) && [] === $value)) {
            return $this->getMessage($attribute, 'required', sprintf('The %s field is required.', $attribute));
        }
        return null;
    }

    private function validateEmail(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->getMessage($attribute, 'email', sprintf('The %s must be a valid email address.', $attribute));
        }
        return null;
    }

    private function validateString(string $attribute, mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!is_string($value)) {
            return $this->getMessage($attribute, 'string', sprintf('The %s must be a string.', $attribute));
        }
        return null;
    }

    private function validateInteger(string $attribute, mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!is_int($value) && !ctype_digit((string)$value)) {
            return $this->getMessage($attribute, 'integer', sprintf('The %s must be an integer.', $attribute));
        }
        return null;
    }

    private function validateNumeric(string $attribute, mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!is_numeric($value)) {
            return $this->getMessage($attribute, 'numeric', sprintf('The %s must be a number.', $attribute));
        }
        return null;
    }

    private function validateBoolean(string $attribute, mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return $this->getMessage($attribute, 'boolean', sprintf('The %s must be true or false.', $attribute));
        }
        return null;
    }

    private function validateArray(string $attribute, mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!is_array($value)) {
            return $this->getMessage($attribute, 'array', sprintf('The %s must be an array.', $attribute));
        }
        return null;
    }

    private function validateMin(string $attribute, mixed $value, int|float $min): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min) {
                return $this->getMessage(
                    $attribute,
                    'min',
                    sprintf('The %s must be at least %s characters.', $attribute, $min)
                );
            }
        } elseif (is_numeric($value)) {
            if ($value < $min) {
                return $this->getMessage($attribute, 'min', sprintf('The %s must be at least %s.', $attribute, $min));
            }
        } elseif (is_array($value)) {
            $count = count($value);
            if ($count < $min) {
                return $this->getMessage(
                    $attribute,
                    'min',
                    sprintf('The %s must have at least %s items.', $attribute, $min)
                );
            }
        }

        return null;
    }

    private function validateMax(string $attribute, mixed $value, int|float $max): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length > $max) {
                return $this->getMessage(
                    $attribute,
                    'max',
                    sprintf('The %s must not exceed %s characters.', $attribute, $max)
                );
            }
        } elseif (is_numeric($value)) {
            if ($value > $max) {
                return $this->getMessage($attribute, 'max', sprintf('The %s must not exceed %s.', $attribute, $max));
            }
        } elseif (is_array($value)) {
            $count = count($value);
            if ($count > $max) {
                return $this->getMessage(
                    $attribute,
                    'max',
                    sprintf('The %s must not have more than %s items.', $attribute, $max)
                );
            }
        }

        return null;
    }

    private function validateBetween(string $attribute, mixed $value, int|float $min, int|float $max): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min || $length > $max) {
                return $this->getMessage(
                    $attribute,
                    'between',
                    sprintf('The %s must be between %s and %s characters.', $attribute, $min, $max)
                );
            }
        } elseif (is_numeric($value)) {
            if ($value < $min || $value > $max) {
                return $this->getMessage(
                    $attribute,
                    'between',
                    sprintf('The %s must be between %s and %s.', $attribute, $min, $max)
                );
            }
        }

        return null;
    }

    /** @param array<string> $allowed */
    private function validateIn(string $attribute, mixed $value, array $allowed): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!in_array($value, $allowed, true)) {
            $list = implode(', ', $allowed);
            return $this->getMessage($attribute, 'in', sprintf('The %s must be one of: %s.', $attribute, $list));
        }
        return null;
    }

    /** @param array<string> $disallowed */
    private function validateNotIn(string $attribute, mixed $value, array $disallowed): ?string
    {
        if (null === $value) {
            return null;
        }
        if (in_array($value, $disallowed, true)) {
            return $this->getMessage($attribute, 'not_in', sprintf('The %s contains an invalid value.', $attribute));
        }
        return null;
    }

    private function validateRegex(string $attribute, mixed $value, string $pattern): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!is_string($value) || !preg_match($pattern, $value)) {
            return $this->getMessage($attribute, 'regex', sprintf('The %s format is invalid.', $attribute));
        }
        return null;
    }

    private function validateUrl(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->getMessage($attribute, 'url', sprintf('The %s must be a valid URL.', $attribute));
        }
        return null;
    }

    private function validateUuid(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if (!is_string($value) || !preg_match($pattern, $value)) {
            return $this->getMessage($attribute, 'uuid', sprintf('The %s must be a valid UUID.', $attribute));
        }
        return null;
    }

    private function validateConfirmed(string $field, string $attribute, mixed $value): ?string
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;

        if ($value !== $confirmValue) {
            return $this->getMessage(
                $attribute,
                'confirmed',
                sprintf('The %s confirmation does not match.', $attribute)
            );
        }
        return null;
    }

    private function validateSize(string $attribute, mixed $value, int $size): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            if (mb_strlen($value) !== $size) {
                return $this->getMessage(
                    $attribute,
                    'size',
                    sprintf('The %s must be %d characters.', $attribute, $size)
                );
            }
        } elseif (is_array($value)) {
            if (count($value) !== $size) {
                return $this->getMessage(
                    $attribute,
                    'size',
                    sprintf('The %s must contain %d items.', $attribute, $size)
                );
            }
        } elseif (is_numeric($value)) {
            if ((float)$value !== (float)$size) {
                return $this->getMessage($attribute, 'size', sprintf('The %s must be %d.', $attribute, $size));
            }
        }

        return null;
    }

    private function validateSame(string $field, string $attribute, mixed $value, string $otherField): ?string
    {
        if (null === $value) {
            return null;
        }

        $otherValue = $this->data[$otherField] ?? null;
        if ($value !== $otherValue) {
            return $this->getMessage($attribute, 'same', sprintf('The %s and %s must match.', $attribute, $otherField));
        }

        return null;
    }

    private function validateDifferent(string $field, string $attribute, mixed $value, string $otherField): ?string
    {
        if (null === $value) {
            return null;
        }

        $otherValue = $this->data[$otherField] ?? null;
        if ($value === $otherValue) {
            return $this->getMessage(
                $attribute,
                'different',
                sprintf('The %s and %s must be different.', $attribute, $otherField)
            );
        }

        return null;
    }

    /** @param array<string> $prefixes */
    private function validateStartsWith(string $attribute, mixed $value, array $prefixes): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            return $this->getMessage($attribute, 'starts_with', sprintf('The %s must be a string.', $attribute));
        }

        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return null;
            }
        }

        $prefixList = implode(', ', $prefixes);
        return $this->getMessage(
            $attribute,
            'starts_with',
            sprintf('The %s must start with one of: %s.', $attribute, $prefixList)
        );
    }

    /** @param array<string> $suffixes */
    private function validateEndsWith(string $attribute, mixed $value, array $suffixes): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            return $this->getMessage($attribute, 'ends_with', sprintf('The %s must be a string.', $attribute));
        }

        foreach ($suffixes as $suffix) {
            if (str_ends_with($value, $suffix)) {
                return null;
            }
        }

        $suffixList = implode(', ', $suffixes);
        return $this->getMessage(
            $attribute,
            'ends_with',
            sprintf('The %s must end with one of: %s.', $attribute, $suffixList)
        );
    }

    private function validateIp(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            return $this->getMessage($attribute, 'ip', sprintf('The %s must be a valid IP address.', $attribute));
        }

        return null;
    }

    private function validateIpv4(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->getMessage($attribute, 'ipv4', sprintf('The %s must be a valid IPv4 address.', $attribute));
        }

        return null;
    }

    private function validateIpv6(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->getMessage($attribute, 'ipv6', sprintf('The %s must be a valid IPv6 address.', $attribute));
        }

        return null;
    }

    private function validateJson(string $attribute, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            return $this->getMessage($attribute, 'json', sprintf('The %s must be a valid JSON string.', $attribute));
        }

        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->getMessage($attribute, 'json', sprintf('The %s must be a valid JSON string.', $attribute));
        }

        return null;
    }
}

