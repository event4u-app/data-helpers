<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto;
use Illuminate\Contracts\Validation\Validator;
use RuntimeException;

// Create stub class if Laravel is not installed
if (!class_exists('Illuminate\Foundation\Http\FormRequest')) {
    abstract class FormRequest
    {
        /** @phpstan-ignore-next-line */
        protected function failedValidation(Validator $validator): void {}
        /** @phpstan-ignore-next-line */
        public function validated(): array { return []; }
        /** @phpstan-ignore-next-line */
        public function all(): array { return []; }
    }
} else {
    class_alias('Illuminate\Foundation\Http\FormRequest', 'event4u\DataHelpers\Frameworks\Laravel\FormRequest');
}

/**
 * Base class for Dto-based Form Requests.
 *
 * Combines Laravel's FormRequest with SimpleDto validation.
 *
 * Example:
 * ```php
 * class StoreUserRequest extends DtoFormRequest
 * {
 *     protected string $dtoClass = UserDto::class;
 *
 *     public function authorize(): bool
 *     {
 *         return true;
 *     }
 * }
 *
 * // In controller
 * public function store(StoreUserRequest $request)
 * {
 *     $dto = $request->toDto();
 *     $user = User::create($dto->toArray());
 *     return response()->json($user);
 * }
 * ```
 *
 */
abstract class DtoFormRequest extends FormRequest
{
    /**
     * The Dto class to use.
     *
     * @var class-string<SimpleDto>
     */
    protected string $dtoClass;

    /**
     * Get validation rules from Dto.
     *
     * @return array<string, array<string>|string>
     */
    public function rules(): array
    {
        if (!isset($this->dtoClass)) {
            return [];
        }

        return $this->dtoClass::getAllRules();
    }

    /**
     * Get custom messages from Dto.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        if (!isset($this->dtoClass)) {
            return [];
        }

        /** @phpstan-ignore-next-line */
        return $this->dtoClass::getAllMessages();
    }

    /**
     * Get custom attributes from Dto.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        if (!isset($this->dtoClass)) {
            return [];
        }

        /** @phpstan-ignore-next-line */
        return $this->dtoClass::getAllAttributes();
    }

    /** Convert validated data to Dto. */
    public function toDto(): SimpleDto
    {
        if (!isset($this->dtoClass)) {
            throw new RuntimeException('Dto class not set. Set $dtoClass property in your FormRequest.');
        }

        /** @phpstan-ignore-next-line */
        $validated = $this->validated();
        return $this->dtoClass::fromArray($validated);
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        // Convert Laravel validation errors to our ValidationException
        $errors = $validator->errors()->toArray();
        /** @phpstan-ignore-next-line */
        $data = $this->all();
        throw new ValidationException(
            'The given data was invalid.',
            $errors,
            $data,
            422
        );
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * Override this method in your FormRequest subclass.
     */
    abstract public function authorize(): bool;
}
