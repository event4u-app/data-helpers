<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Validation;

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
                if ($error !== null) {
                    $fieldErrors[] = $error;
                    if ($this->stopOnFirstFailure) {
                        break 2;
                    }
                }
            }

            if (count($fieldErrors) > 0) {
                $this->errors[$field] = $fieldErrors;
            } else {
                $this->validated[$field] = $value;
            }
        }

        if (count($this->errors) > 0) {
            // Use ValidationException for consistency
            throw \event4u\DataHelpers\Exceptions\ValidationException::withMessages($this->errors);
        }

        return $this->validated;
    }

    /**
     * Check if validation fails.
     */
    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException) {
            return true;
        }
    }

    /**
     * Check if validation passes.
     */
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

    /**
     * Set stop on first failure.
     */
    public function stopOnFirstFailure(bool $stop = true): self
    {
        $this->stopOnFirstFailure = $stop;
        return $this;
    }

    /**
     * Validate a single rule.
     */
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
            'between' => $this->validateBetween($attribute, $value, (float)($parameters[0] ?? 0), (float)($parameters[1] ?? 0)),
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

    /**
     * Get custom error message or default.
     */
    private function getMessage(string $field, string $rule, string $default): string
    {
        return $this->messages["$field.$rule"] ?? $this->messages[$rule] ?? $default;
    }

    // Validation methods
    private function validateRequired(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            return $this->getMessage($attribute, 'required', "The $attribute field is required.");
        }
        return null;
    }

    private function validateEmail(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->getMessage($attribute, 'email', "The $attribute must be a valid email address.");
        }
        return null;
    }

    private function validateString(string $attribute, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            return $this->getMessage($attribute, 'string', "The $attribute must be a string.");
        }
        return null;
    }

    private function validateInteger(string $attribute, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_int($value) && !ctype_digit((string)$value)) {
            return $this->getMessage($attribute, 'integer', "The $attribute must be an integer.");
        }
        return null;
    }

    private function validateNumeric(string $attribute, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_numeric($value)) {
            return $this->getMessage($attribute, 'numeric', "The $attribute must be a number.");
        }
        return null;
    }

    private function validateBoolean(string $attribute, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return $this->getMessage($attribute, 'boolean', "The $attribute must be true or false.");
        }
        return null;
    }

    private function validateArray(string $attribute, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            return $this->getMessage($attribute, 'array', "The $attribute must be an array.");
        }
        return null;
    }

    private function validateMin(string $attribute, mixed $value, int|float $min): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min) {
                return $this->getMessage($attribute, 'min', "The $attribute must be at least $min characters.");
            }
        } elseif (is_numeric($value)) {
            if ($value < $min) {
                return $this->getMessage($attribute, 'min', "The $attribute must be at least $min.");
            }
        } elseif (is_array($value)) {
            $count = count($value);
            if ($count < $min) {
                return $this->getMessage($attribute, 'min', "The $attribute must have at least $min items.");
            }
        }

        return null;
    }

    private function validateMax(string $attribute, mixed $value, int|float $max): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length > $max) {
                return $this->getMessage($attribute, 'max', "The $attribute must not exceed $max characters.");
            }
        } elseif (is_numeric($value)) {
            if ($value > $max) {
                return $this->getMessage($attribute, 'max', "The $attribute must not exceed $max.");
            }
        } elseif (is_array($value)) {
            $count = count($value);
            if ($count > $max) {
                return $this->getMessage($attribute, 'max', "The $attribute must not have more than $max items.");
            }
        }

        return null;
    }

    private function validateBetween(string $attribute, mixed $value, int|float $min, int|float $max): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min || $length > $max) {
                return $this->getMessage($attribute, 'between', "The $attribute must be between $min and $max characters.");
            }
        } elseif (is_numeric($value)) {
            if ($value < $min || $value > $max) {
                return $this->getMessage($attribute, 'between', "The $attribute must be between $min and $max.");
            }
        }

        return null;
    }

    /**
     * @param array<string> $allowed
     */
    private function validateIn(string $attribute, mixed $value, array $allowed): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!in_array($value, $allowed, true)) {
            $list = implode(', ', $allowed);
            return $this->getMessage($attribute, 'in', "The $attribute must be one of: $list.");
        }
        return null;
    }

    /**
     * @param array<string> $disallowed
     */
    private function validateNotIn(string $attribute, mixed $value, array $disallowed): ?string
    {
        if ($value === null) {
            return null;
        }
        if (in_array($value, $disallowed, true)) {
            return $this->getMessage($attribute, 'not_in', "The $attribute contains an invalid value.");
        }
        return null;
    }

    private function validateRegex(string $attribute, mixed $value, string $pattern): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) || !preg_match($pattern, $value)) {
            return $this->getMessage($attribute, 'regex', "The $attribute format is invalid.");
        }
        return null;
    }

    private function validateUrl(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->getMessage($attribute, 'url', "The $attribute must be a valid URL.");
        }
        return null;
    }

    private function validateUuid(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if (!is_string($value) || !preg_match($pattern, $value)) {
            return $this->getMessage($attribute, 'uuid', "The $attribute must be a valid UUID.");
        }
        return null;
    }

    private function validateConfirmed(string $field, string $attribute, mixed $value): ?string
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;

        if ($value !== $confirmValue) {
            return $this->getMessage($attribute, 'confirmed', "The $attribute confirmation does not match.");
        }
        return null;
    }

    private function validateSize(string $attribute, mixed $value, int $size): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            if (mb_strlen($value) !== $size) {
                return $this->getMessage($attribute, 'size', "The $attribute must be $size characters.");
            }
        } elseif (is_array($value)) {
            if (count($value) !== $size) {
                return $this->getMessage($attribute, 'size', "The $attribute must contain $size items.");
            }
        } elseif (is_numeric($value)) {
            if ((float)$value !== (float)$size) {
                return $this->getMessage($attribute, 'size', "The $attribute must be $size.");
            }
        }

        return null;
    }

    private function validateSame(string $field, string $attribute, mixed $value, string $otherField): ?string
    {
        if ($value === null) {
            return null;
        }

        $otherValue = $this->data[$otherField] ?? null;
        if ($value !== $otherValue) {
            return $this->getMessage($attribute, 'same', "The $attribute and $otherField must match.");
        }

        return null;
    }

    private function validateDifferent(string $field, string $attribute, mixed $value, string $otherField): ?string
    {
        if ($value === null) {
            return null;
        }

        $otherValue = $this->data[$otherField] ?? null;
        if ($value === $otherValue) {
            return $this->getMessage($attribute, 'different', "The $attribute and $otherField must be different.");
        }

        return null;
    }

    /**
     * @param array<string> $prefixes
     */
    private function validateStartsWith(string $attribute, mixed $value, array $prefixes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return $this->getMessage($attribute, 'starts_with', "The $attribute must be a string.");
        }

        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return null;
            }
        }

        $prefixList = implode(', ', $prefixes);
        return $this->getMessage($attribute, 'starts_with', "The $attribute must start with one of: $prefixList.");
    }

    /**
     * @param array<string> $suffixes
     */
    private function validateEndsWith(string $attribute, mixed $value, array $suffixes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return $this->getMessage($attribute, 'ends_with', "The $attribute must be a string.");
        }

        foreach ($suffixes as $suffix) {
            if (str_ends_with($value, $suffix)) {
                return null;
            }
        }

        $suffixList = implode(', ', $suffixes);
        return $this->getMessage($attribute, 'ends_with', "The $attribute must end with one of: $suffixList.");
    }

    private function validateIp(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            return $this->getMessage($attribute, 'ip', "The $attribute must be a valid IP address.");
        }

        return null;
    }

    private function validateIpv4(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->getMessage($attribute, 'ipv4', "The $attribute must be a valid IPv4 address.");
        }

        return null;
    }

    private function validateIpv6(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->getMessage($attribute, 'ipv6', "The $attribute must be a valid IPv6 address.");
        }

        return null;
    }

    private function validateJson(string $attribute, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return $this->getMessage($attribute, 'json', "The $attribute must be a valid JSON string.");
        }

        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->getMessage($attribute, 'json', "The $attribute must be a valid JSON string.");
        }

        return null;
    }
}

