<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Validation\ValidationResult;
use event4u\DataHelpers\Validation\Validator;
use ReflectionClass;

/**
 * Trait for automatic request validation.
 *
 * Provides framework-independent validation methods that can be used
 * standalone or integrated with Laravel/Symfony.
 *
 * Example:
 * ```php
 * #[ValidateRequest(throw: true)]
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Required, Email]
 *         public readonly string $email,
 *
 *         #[Required, Min(3)]
 *         public readonly string $name,
 *     ) {}
 * }
 *
 * // Validate and create (throws on failure)
 * $dto = UserDTO::validateAndCreate($data);
 *
 * // Validate without throwing
 * $result = UserDTO::validateData($data);
 * if ($result->isValid()) {
 *     $dto = UserDTO::fromArray($result->validated());
 * }
 * ```
 */
trait SimpleDTORequestValidationTrait
{
    /**
     * Validate data and return ValidationResult.
     *
     * Does not throw exception, returns result object instead.
     *
     * @param array<string, mixed> $data
     */
    public static function validateData(array $data): ValidationResult
    {
        try {
            $validated = static::performValidation($data, false);
            return ValidationResult::success($validated);
        } catch (ValidationException $validationException) {
            return ValidationResult::failure($validationException->errors());
        }
    }

    /**
     * Validate data and throw exception on failure.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed> Validated data
     * @throws ValidationException
     */
    public static function validateOrFail(array $data): array
    {
        return static::performValidation($data, true);
    }

    /** Check if DTO has ValidateRequest attribute. */
    public static function shouldAutoValidate(): bool
    {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(ValidateRequest::class);
        return $attributes !== [];
    }

    /** Get ValidateRequest attribute if present. */
    public static function getValidateRequestAttribute(): ?ValidateRequest
    {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(ValidateRequest::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Perform validation using framework-independent validator.
     *
     * @param array<string, mixed> $data
     * @param bool $throw Whether to throw exception on failure
     * @return array<string, mixed> Validated data
     * @throws ValidationException
     */
    private static function performValidation(array $data, bool $throw): array
    {
        // Get validation rules
        $rules = static::getAllRules();
        $messages = static::getAllMessages();
        $attributes = static::getAllAttributes();

        // Check for ValidateRequest attribute
        $validateAttr = static::getValidateRequestAttribute();

        // Filter rules if only/except specified
        if ($validateAttr instanceof ValidateRequest) {
            if ($validateAttr->only !== []) {
                $rules = array_intersect_key($rules, array_flip($validateAttr->only));
            }
            if ($validateAttr->except !== []) {
                $rules = array_diff_key($rules, array_flip($validateAttr->except));
            }
        }

        // Try Laravel validator first (if available)
        if (static::hasLaravelValidator()) {
            return static::validateWithLaravel($data, $rules, $messages, $attributes);
        }

        // Fallback to framework-independent validator
        $validator = new Validator($data, $rules, $messages, $attributes);

        if ($validateAttr?->stopOnFirstFailure) {
            $validator->stopOnFirstFailure();
        }

        try {
            return $validator->validate();
        } catch (ValidationException $validationException) {
            if ($throw || $validateAttr?->throw) {
                throw $validationException;
            }
            throw $validationException; // Always throw for now, ValidationResult is handled in validateData()
        }
    }

    /**
     * Validate using Laravel validator.
     *
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private static function validateWithLaravel(
        array $data,
        array $rules,
        array $messages,
        array $attributes
    ): array {
        $validator = app('validator')->make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /**
     * Get all validation rules (from SimpleDTOValidationTrait).
     *
     * @return array<string, array<string>>
     */
    abstract protected static function getAllRules(): array;

    /**
     * Get all custom messages (from SimpleDTOValidationTrait).
     *
     * @return array<string, string>
     */
    abstract protected static function getAllMessages(): array;

    /**
     * Get all custom attributes (from SimpleDTOValidationTrait).
     *
     * @return array<string, string>
     */
    abstract protected static function getAllAttributes(): array;
}

