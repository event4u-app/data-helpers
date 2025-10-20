<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes\Laravel;

use Illuminate\Support\Facades\Auth;
use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;
use Throwable;

/**
 * Attribute to conditionally include a property when user is a guest (not authenticated).
 *
 * Checks for guest user in two ways:
 * 1. Context: $dto->withContext(['user' => null])
 * 2. Laravel Auth facade: Auth::guest() (if available)
 *
 * @example With context
 * ```php
 * class PageDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $title,
 *
 *         #[WhenGuest]
 *         public readonly string $loginPrompt = 'Please log in',
 *     ) {}
 * }
 *
 * $dto = new PageDTO('Home');
 * $dto->withContext(['user' => null])->toArray();
 * // ['title' => 'Home', 'loginPrompt' => 'Please log in']
 * ```
 *
 * @example With Laravel Auth
 * ```php
 * // Automatically uses Auth::guest()
 * $dto->toArray();
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenGuest implements ConditionalProperty
{
    /** @param string|null $guard Guard name (Laravel only) */
    public function __construct(
        public readonly ?string $guard = null,
    ) {}

    /**
     * Check if the property should be included based on guest status.
     *
     * @param mixed $value Property value
     * @param object $dto DTO instance
     * @param array<string, mixed> $context Context data
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check context first
        if (array_key_exists('user', $context)) {
            return null === $context['user'];
        }

        // Fall back to Laravel Auth if available
        if (class_exists('Illuminate\Support\Facades\Auth')) {
            try {
                $auth = Auth::guard($this->guard);
                return $auth->guest();
            } catch (Throwable) {
                // Laravel not properly initialized, assume guest
                return true;
            }
        }

        // Default to true if no context and no Laravel (assume guest)
        return true;
    }
}

