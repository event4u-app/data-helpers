<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Laravel;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Exceptions\ValidationException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Laravel Value Resolver for automatic DTO injection in controllers.
 *
 * Automatically creates and validates DTOs when injected into controller methods.
 *
 * Example:
 * ```php
 * class UserController extends Controller
 * {
 *     public function store(UserDTO $dto)
 *     {
 *         // $dto is automatically created and validated from request
 *         $user = User::create($dto->toArray());
 *         return response()->json($user);
 *     }
 * }
 * ```
 */
class DTOValueResolver
{
    public function __construct(
        private readonly Request $request,
        private readonly ValidationFactory $validator,
    ) {}

    /**
     * Resolve DTO from request.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
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

        // Only handle SimpleDTO subclasses
        if (!is_subclass_of($className, SimpleDTO::class)) {
            return null;
        }

        // Get request data
        $data = $this->getRequestData();

        // Check if DTO has ValidateRequest attribute
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes(ValidateRequest::class);

        if (count($attributes) > 0) {
            /** @var ValidateRequest $validateAttr */
            $validateAttr = $attributes[0]->newInstance();

            // Validate and create
            if ($validateAttr->throw) {
                return $className::validateAndCreate($data);
            }

            // Validate without throwing
            $result = $className::validateData($data);
            if ($result->isFailed()) {
                throw new ValidationException($result->errors(), $data);
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
        if ($this->request->isJson()) {
            return $this->request->json()->all();
        }

        // Fallback to all request data
        return $this->request->all();
    }
}

