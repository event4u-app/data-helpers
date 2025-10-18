<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Exceptions\ValidationException;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Symfony Value Resolver for automatic DTO injection in controllers.
 *
 * Automatically creates and validates DTOs when injected into controller methods.
 *
 * Example:
 * ```php
 * class UserController extends AbstractController
 * {
 *     #[Route('/users', methods: ['POST'])]
 *     public function store(UserDTO $dto): Response
 *     {
 *         // $dto is automatically created and validated from request
 *         $user = new User();
 *         $user->setEmail($dto->email);
 *         $user->setName($dto->name);
 *
 *         $this->entityManager->persist($user);
 *         $this->entityManager->flush();
 *
 *         return $this->json($user);
 *     }
 * }
 * ```
 */
class DTOValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly ?ValidatorInterface $validator = null,
    ) {}

    /**
     * Resolve DTO from request.
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return iterable<mixed>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        // Only handle class types
        if (!$type || !class_exists($type)) {
            return [];
        }

        // Only handle SimpleDTO subclasses
        if (!is_subclass_of($type, SimpleDTO::class)) {
            return [];
        }

        // Get request data
        $data = $this->getRequestData($request);

        // Check if DTO has ValidateRequest attribute
        $reflection = new ReflectionClass($type);
        $attributes = $reflection->getAttributes(ValidateRequest::class);

        if (count($attributes) > 0) {
            /** @var ValidateRequest $validateAttr */
            $validateAttr = $attributes[0]->newInstance();

            // Validate and create
            if ($validateAttr->throw) {
                yield $type::validateAndCreate($data);
                return;
            }

            // Validate without throwing
            $result = $type::validateData($data);
            if ($result->isFailed()) {
                throw new ValidationException($result->errors(), $data);
            }

            yield $type::fromArray($result->validated());
            return;
        }

        // No validation, just create
        yield $type::fromArray($data);
    }

    /**
     * Get request data (JSON or form data).
     *
     * @return array<string, mixed>
     */
    private function getRequestData(Request $request): array
    {
        // Try JSON first
        $content = $request->getContent();
        if (!empty($content)) {
            $json = json_decode($content, true);
            if (is_array($json)) {
                return $json;
            }
        }

        // Fallback to request parameters
        return $request->request->all();
    }
}

