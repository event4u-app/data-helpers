<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use Throwable;

/**
 * Include property only when user has the specified permission/attribute.
 *
 * Works with Symfony Security component.
 *
 * Usage:
 * - #[WhenGranted('ROLE_ADMIN')] - Check if user has role
 * - #[WhenGranted('EDIT', 'subject')] - Check if user can edit subject
 * - #[WhenGranted('VIEW', Post::class)] - Check if user can view Post
 *
 * Context-based usage:
 * - Pass security context: $dto->withContext(['security' => $security])
 * - Pass user context: $dto->withContext(['user' => $user])
 *
 * Symfony facade usage (when Symfony is installed):
 * - Uses Security service if available
 * - Falls back to context-based check
 *
 * @package event4u\DataHelpers\SimpleDto\Attributes
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenGranted implements ConditionalProperty
{
    /**
     * @param string $attribute The attribute/permission to check (e.g., 'ROLE_ADMIN', 'EDIT')
     * @param mixed $subject Optional subject to check permission against
     */
    public function __construct(
        public readonly string $attribute,
        public readonly mixed $subject = null,
    ) {}

    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Try Symfony Security service first
        if (class_exists('Symfony\Component\Security\Core\Security')) {
            try {
                // Try to get Security from context
                if (isset($context['security'])) {
                    $security = $context['security'];
                    if (is_object($security) && method_exists($security, 'isGranted')) {
                        return $security->isGranted($this->attribute, $this->subject);
                    }
                }

                // Try to get AuthorizationChecker from context
                if (isset($context['authorization_checker'])) {
                    $checker = $context['authorization_checker'];
                    if (is_object($checker) && method_exists($checker, 'isGranted')) {
                        return $checker->isGranted($this->attribute, $this->subject);
                    }
                }
            } catch (Throwable) {
                // Silently fail if Symfony Security is not available
            }
        }

        // Fallback: Check user context
        if (isset($context['user'])) {
            $user = $context['user'];

            // Check if user has method to check permission
            if (is_object($user) && method_exists($user, 'isGranted')) {
                return $user->isGranted($this->attribute, $this->subject);
            }

            // Check roles if attribute starts with ROLE_
            if (is_object($user) && str_starts_with($this->attribute, 'ROLE_') && method_exists($user, 'getRoles')) {
                $roles = $user->getRoles();
                return in_array($this->attribute, $roles, true);
            }
        }

        // Default: not granted
        return false;
    }
}
