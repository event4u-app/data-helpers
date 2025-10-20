<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;
use Throwable;

/**
 * Include property only when user has the specified Symfony role(s).
 *
 * Works with Symfony Security component.
 *
 * Usage:
 * - #[WhenSymfonyRole('ROLE_ADMIN')] - Single role
 * - #[WhenSymfonyRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])] - Multiple roles (OR logic)
 *
 * Context-based usage:
 * - Pass security context: $dto->withContext(['security' => $security])
 * - Pass user context: $dto->withContext(['user' => $user])
 *
 * Symfony facade usage (when Symfony is installed):
 * - Uses Security service if available
 * - Falls back to context-based check
 *
 * @package event4u\DataHelpers\SimpleDTO\Attributes
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenSymfonyRole implements ConditionalProperty
{
    /** @var array<string> */
    private readonly array $roles;

    /**
     * @param string|array<string> $roles Role(s) to check
     */
    public function __construct(
        string|array $roles,
    ) {
        $this->roles = is_array($roles) ? $roles : [$roles];
    }

    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Try Symfony Security service first
        if (class_exists('Symfony\Component\Security\Core\Security')) {
            try {
                // Try to get Security from context
                if (isset($context['security'])) {
                    $security = $context['security'];
                    if (is_object($security) && method_exists($security, 'isGranted')) {
                        foreach ($this->roles as $role) {
                            if ($security->isGranted($role)) {
                                return true;
                            }
                        }
                        return false;
                    }
                }

                // Try to get AuthorizationChecker from context
                if (isset($context['authorization_checker'])) {
                    $checker = $context['authorization_checker'];
                    if (is_object($checker) && method_exists($checker, 'isGranted')) {
                        foreach ($this->roles as $role) {
                            if ($checker->isGranted($role)) {
                                return true;
                            }
                        }
                        return false;
                    }
                }
            } catch (Throwable) {
                // Silently fail if Symfony Security is not available
            }
        }

        // Fallback: Check user context
        if (isset($context['user'])) {
            $user = $context['user'];

            // Check if user has getRoles method
            if (is_object($user) && method_exists($user, 'getRoles')) {
                $userRoles = $user->getRoles();
                foreach ($this->roles as $role) {
                    if (in_array($role, $userRoles, true)) {
                        return true;
                    }
                }
            }
        }

        // Default: not granted
        return false;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}

