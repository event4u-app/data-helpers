<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Pipeline;

use event4u\DataHelpers\SimpleDTO\Exceptions\ValidationException;

/**
 * Pipeline stage that validates data.
 */
class ValidationStage implements PipelineStageInterface
{
    /**
     * @param array<string, array<string>> $rules Validation rules
     * @param string $name Stage name
     */
    public function __construct(
        private readonly array $rules = [],
        private readonly string $name = 'validation'
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function process(array $data): array
    {
        $errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->validateRule($field, $value, $rule);

                if (null !== $error) {
                    $errors[$field][] = $error;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Validate a single rule.
     */
    private function validateRule(string $field, mixed $value, string $rule): ?string
    {
        return match ($rule) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            default => $this->validateCustomRule($field, $value, $rule),
        };
    }

    private function validateRequired(string $field, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return "The {$field} field is required.";
        }

        return null;
    }

    private function validateEmail(string $field, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} must be a valid email address.";
        }

        return null;
    }

    private function validateNumeric(string $field, mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_numeric($value)) {
            return "The {$field} must be numeric.";
        }

        return null;
    }

    private function validateCustomRule(string $field, mixed $value, string $rule): ?string
    {
        // Handle min:X, max:X, etc.
        if (str_contains($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule, 2);

            return match ($ruleName) {
                'min' => $this->validateMin($field, $value, (int) $parameter),
                'max' => $this->validateMax($field, $value, (int) $parameter),
                default => null,
            };
        }

        return null;
    }

    private function validateMin(string $field, mixed $value, int $min): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_numeric($value) && $value < $min) {
            return "The {$field} must be at least {$min}.";
        }

        if (is_string($value) && strlen($value) < $min) {
            return "The {$field} must be at least {$min} characters.";
        }

        return null;
    }

    private function validateMax(string $field, mixed $value, int $max): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_numeric($value) && $value > $max) {
            return "The {$field} must not be greater than {$max}.";
        }

        if (is_string($value) && strlen($value) > $max) {
            return "The {$field} must not be greater than {$max} characters.";
        }

        return null;
    }
}

