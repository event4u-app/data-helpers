<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Laravel;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Attribute to conditionally include a property when user is authenticated.
 *
 * Checks for authenticated user in two ways:
 * 1. Context: $dto->withContext(['user' => $user])
 * 2. Laravel Auth facade: Auth::check() (if available)
 *
 * @example With context
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenAuth]
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * $dto = new UserDto('John', 'john@example.com');
 * $dto->withContext(['user' => $authenticatedUser])->toArray();
 * // ['name' => 'John', 'email' => 'john@example.com']
 * ```
 *
 * @example With Laravel Auth
 * ```php
 * // Automatically uses Auth::user()
 * $dto->toArray();
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenAuth implements ConditionalProperty
{
    /** @param string|null $guard Guard name (Laravel only) */
    public function __construct(
        public readonly ?string $guard = null,
    ) {}

    /**
     * Check if the property should be included based on authentication.
     *
     * @param mixed $value Property value
     * @param object $dto Dto instance
     * @param array<string, mixed> $context Context data
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check context first
        if (array_key_exists('user', $context)) {
            return null !== $context['user'];
        }

        // Fall back to Laravel Auth if available
        if (class_exists('Illuminate\Support\Facades\Auth')) {
            try {
                $auth = Auth::guard($this->guard);
                return $auth->check();
            } catch (Throwable) {
                // Laravel not properly initialized, treat as not authenticated
                return false;
            }
        }

        // Default to false if no context and no Laravel
        return false;
    }
}
