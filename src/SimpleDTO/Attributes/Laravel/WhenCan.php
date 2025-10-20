<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes\Laravel;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Attribute to conditionally include a property based on authorization.
 *
 * Checks authorization in two ways:
 * 1. Context: Pass user and check if user can perform ability
 * 2. Laravel Gate facade: Gate::allows() (if available)
 *
 * Supports multiple syntaxes:
 * - WhenCan('edit-post') - Check ability
 * - WhenCan('edit', 'App\Models\Post') - Check ability with model class
 * - WhenCan('edit', 'post') - Check ability with model from context['post']
 *
 * @example With context
 * ```php
 * class PostDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $title,
 *
 *         #[WhenCan('edit-post')]
 *         public readonly string $editLink = '/edit',
 *     ) {}
 * }
 *
 * $user = (object)['can' => fn($ability) => $ability === 'edit-post'];
 * $dto = new PostDTO('My Post');
 * $dto->withContext(['user' => $user])->toArray();
 * ```
 *
 * @example With Laravel Gate
 * ```php
 * // Automatically uses Gate::allows()
 * $dto->toArray();
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenCan implements ConditionalProperty
{
    /**
     * @param string $ability Ability to check
     * @param string|null $model Model class or context key
     */
    public function __construct(
        public readonly string $ability,
        public readonly ?string $model = null,
    ) {}

    /**
     * Check if the property should be included based on authorization.
     *
     * @param mixed $value Property value
     * @param object $dto DTO instance
     * @param array<string, mixed> $context Context data
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check context first
        if (array_key_exists('user', $context)) {
            $user = $context['user'];

            // No user = no permission
            if (null === $user) {
                return false;
            }

            // Get model argument if specified
            $modelArgument = $this->getModelArgument($context);

            // Check if user has 'can' method (Laravel User model)
            if (is_object($user) && method_exists($user, 'can')) {
                return null !== $modelArgument
                    ? $user->can($this->ability, $modelArgument)
                    : $user->can($this->ability);
            }

            // Check if user has 'abilities' or 'permissions' array
            if (is_object($user) && isset($user->abilities)) {
                return in_array($this->ability, $user->abilities, true);
            }

            if (is_object($user) && isset($user->permissions)) {
                return in_array($this->ability, $user->permissions, true);
            }

            // Default to false if user doesn't have can method
            return false;
        }

        // Fall back to Laravel Gate if available
        if (class_exists('Illuminate\Support\Facades\Gate')) {
            try {
                $modelArgument = $this->getModelArgument($context);

                return null !== $modelArgument
                    ? Gate::allows($this->ability, $modelArgument)
                    : Gate::allows($this->ability);
            } catch (Throwable) {
                // Laravel not properly initialized, treat as no permission
                return false;
            }
        }

        // Default to false if no context and no Laravel
        return false;
    }

    /**
     * Get model argument from context.
     *
     * @param array<string, mixed> $context Context data
     */
    private function getModelArgument(array $context): mixed
    {
        if (null === $this->model) {
            return null;
        }

        // If model is a class name, return it
        if (class_exists($this->model)) {
            return $this->model;
        }

        // Otherwise, try to get it from context
        return $context[$this->model] ?? null;
    }
}

