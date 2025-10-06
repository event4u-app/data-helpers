<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Integration;

use event4u\DataHelpers\MappedDataModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Symfony Value Resolver for automatic MappedDataModel binding.
 *
 * This resolver enables automatic dependency injection of MappedDataModel subclasses
 * in Symfony controllers.
 *
 * ## Automatic Registration (Symfony Flex)
 *
 * If you're using **Symfony Flex**, this resolver is automatically registered via the
 * `SymfonyDataHelpersBundle` configured in `composer.json`.
 *
 * **No manual configuration needed!** Just install the package:
 *
 * ```bash
 * composer require event4u/data-helpers
 * ```
 *
 * ## Manual Registration (Without Flex)
 *
 * If you're **not using Symfony Flex**, register this resolver in `config/services.yaml`:
 *
 * ```yaml
 * services:
 *     event4u\DataHelpers\Integration\SymfonyMappedModelResolver:
 *         tags:
 *             - { name: controller.argument_value_resolver, priority: 50 }
 * ```
 *
 * Or use autoconfigure (Symfony 6.1+):
 *
 * ```yaml
 * services:
 *     _defaults:
 *         autoconfigure: true
 *
 *     event4u\DataHelpers\Integration\SymfonyMappedModelResolver: ~
 * ```
 *
 * ## Usage in Controllers
 *
 * Simply type-hint your MappedDataModel subclass in controller methods:
 *
 * ```php
 * class UserController extends AbstractController
 * {
 *     #[Route('/register', methods: ['POST'])]
 *     public function register(UserRegistrationModel $model): JsonResponse
 *     {
 *         // $model is automatically instantiated with request data
 *         $user = $this->userRepository->create($model->toArray());
 *         return $this->json($user);
 *     }
 * }
 * ```
 *
 * The model will be automatically filled with the current request data (JSON or form data).
 */
class SymfonyMappedModelResolver implements ValueResolverInterface
{
    /**
     * Resolve the argument value.
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return iterable<MappedDataModel>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Get the argument type
        $type = $argument->getType();

        // Skip if no type or not a class
        if (!$type || !class_exists($type)) {
            return [];
        }

        // Check if it's a MappedDataModel subclass
        if (!is_subclass_of($type, MappedDataModel::class)) {
            return [];
        }

        // Get request data (supports both JSON and form data)
        $data = $this->getRequestData($request);

        // Create and fill the model
        /** @var MappedDataModel $model */
        $model = new $type();
        $model->fill($data);

        yield $model;
    }

    /**
     * Get request data from various sources.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    private function getRequestData(Request $request): array
    {
        // Try JSON content first
        $content = $request->getContent();
        if (!empty($content)) {
            try {
                $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($json)) {
                    return $json;
                }
            } catch (\JsonException) {
                // Not JSON, continue
            }
        }

        // Fallback to request parameters
        return $request->request->all();
    }
}

