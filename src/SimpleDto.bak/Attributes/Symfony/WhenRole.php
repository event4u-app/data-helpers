<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Symfony;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

/**
 * Attribute to conditionally include a property based on Symfony user role.
 *
 * Checks user role in two ways:
 * 1. Context: Pass user with roles
 * 2. Symfony Security: Use Security->isGranted('ROLE_ADMIN') (if available)
 *
 * Supports multiple syntaxes:
 * - WhenRole('ROLE_ADMIN') - Check single role
 * - WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR']) - Check multiple roles (OR logic)
 *
 * @example With context (single role)
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenRole('ROLE_ADMIN')]
 *         public readonly string $adminPanel = '/admin',
 *     ) {}
 * }
 *
 * $user = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
 * $dto = new UserDto('John');
 * $dto->withContext(['user' => $user])->toArray();
 * ```
 *
 * @example With context (multiple roles)
 * ```php
 * #[WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
 * public readonly string $moderationPanel = '/moderation';
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenRole implements ConditionalProperty
{
    /** @var array<string> */
    private readonly array $roles;

    /** @param string|array<string> $role Role(s) to check (e.g., 'ROLE_ADMIN', ['ROLE_ADMIN', 'ROLE_MODERATOR']) */
    public function __construct(
        string|array $role,
    ) {
        $this->roles = is_array($role) ? $role : [$role];
    }

    /**
     * Check if the property should be included based on user role.
     *
     * @param mixed $value Property value
     * @param object $dto Dto instance
     * @param array<string, mixed> $context Context data
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check context first
        if (array_key_exists('user', $context)) {
            $user = $context['user'];

            // No user = no role
            if (null === $user) {
                return false;
            }

            // Check if user has getRoles method (Symfony UserInterface)
            if (is_object($user) && method_exists($user, 'getRoles')) {
                $userRoles = $user->getRoles();
                foreach ($this->roles as $role) {
                    if (in_array($role, $userRoles, true)) {
                        return true;
                    }
                }
                return false;
            }

            // Check if user has roles property (array)
            if (is_object($user) && isset($user->roles)) {
                $userRoles = is_array($user->roles) ? $user->roles : [$user->roles];
                foreach ($this->roles as $role) {
                    if (in_array($role, $userRoles, true)) {
                        return true;
                    }
                }
                return false;
            }

            // Check if user has role property (string)
            if (is_object($user) && isset($user->role)) {
                return in_array($user->role, $this->roles, true);
            }

            // Default to false if user doesn't have role info
            return false;
        }

        // Check if 'security' is in context (Symfony AuthorizationCheckerInterface)
        if (array_key_exists('security', $context)) {
            $security = $context['security'];

            if (null !== $security && is_object($security) && method_exists($security, 'isGranted')) {
                foreach ($this->roles as $role) {
                    if ($security->isGranted($role)) {
                        return true;
                    }
                }
                return false;
            }
        }

        // Default to false if no context
        return false;
    }
}
