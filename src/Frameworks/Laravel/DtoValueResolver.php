<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ValidateRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Laravel Value Resolver for automatic Dto injection in controllers.
 *
 * Automatically creates and validates Dtos when injected into controller methods.
 *
 * Example:
 * ```php
 * class UserController extends Controller
 * {
 *     public function store(UserDto $dto)
 *     {
 *         // $dto is automatically created and validated from request
 *         $user = User::create($dto->toArray());
 *         return response()->json($user);
 *     }
 * }
 * ```
 */
class DtoValueResolver
{
    public function __construct(
        /** @phpstan-ignore-next-line */
        private readonly Request $request,
        /** @phpstan-ignore-next-line */
        private readonly ValidationFactory $validator
    )
    {
    }

    /**
     * Resolve Dto from request.
     *
     * @throws ValidationException
     */
    public function resolve(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // Only handle named types
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $className = $type->getName();

        // Only handle SimpleDto subclasses
        if (!is_subclass_of($className, SimpleDto::class)) {
            return null;
        }

        // Get request data
        $data = $this->getRequestData();

        // Check if Dto has ValidateRequest attribute
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes(ValidateRequest::class);

        if ([] !== $attributes) {
            /** @var ValidateRequest $validateAttr */
            $validateAttr = $attributes[0]->newInstance();

            // Validate and create
            if ($validateAttr->throw) {
                return $className::validateAndCreate($data);
            }

            // Validate without throwing
            $result = $className::validateData($data);
            if ($result->isFailed()) {
                throw new ValidationException(
                    'Validation failed',
                    $result->errors(),
                    $data
                );
            }

            return $className::fromArray($result->validated());
        }

        // No validation, just create
        return $className::fromArray($data);
    }

    /**
     * Get request data (JSON or form data).
     *
     * @return array<string, mixed>
     */
    private function getRequestData(): array
    {
        // Try JSON first
        /** @phpstan-ignore-next-line */
        if ($this->request->isJson()) {
            /** @phpstan-ignore-next-line */
            return $this->request->json()->all();
        }

        // Fallback to all request data
        /** @phpstan-ignore-next-line */
        return $this->request->all();
    }
}
