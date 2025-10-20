<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Laravel;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use RuntimeException;

/**
 * Base class for DTO-based Form Requests.
 *
 * Combines Laravel's FormRequest with SimpleDTO validation.
 *
 * Example:
 * ```php
 * class StoreUserRequest extends DTOFormRequest
 * {
 *     protected string $dtoClass = UserDTO::class;
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
 *     $dto = $request->toDTO();
 *     $user = User::create($dto->toArray());
 *     return response()->json($user);
 * }
 * ```
 */
abstract class DTOFormRequest extends FormRequest
{
    /**
     * The DTO class to use.
     *
     * @var class-string<SimpleDTO>
     */
    protected string $dtoClass;

    /**
     * Get validation rules from DTO.
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
     * Get custom messages from DTO.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        if (!isset($this->dtoClass)) {
            return [];
        }

        return $this->dtoClass::getAllMessages();
    }

    /**
     * Get custom attributes from DTO.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        if (!isset($this->dtoClass)) {
            return [];
        }

        return $this->dtoClass::getAllAttributes();
    }

    /**
     * Convert validated data to DTO.
     */
    public function toDTO(): SimpleDTO
    {
        if (!isset($this->dtoClass)) {
            throw new RuntimeException('DTO class not set. Set $dtoClass property in your FormRequest.');
        }

        return $this->dtoClass::fromArray($this->validated());
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        // Convert Laravel validation errors to our ValidationException
        throw new ValidationException(
            $validator->errors()->toArray(),
            $this->all(),
            'The given data was invalid.',
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

