<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Laravel;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Attribute to conditionally include a property based on user role.
 *
 * Checks user role in two ways:
 * 1. Context: Pass user with role property
 * 2. Laravel: Check user->hasRole() or user->role (if available)
 *
 * Supports multiple syntaxes:
 * - WhenRole('admin') - Check single role
 * - WhenRole(['admin', 'moderator']) - Check multiple roles (OR logic)
 *
 * @example With context (single role)
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *
 *         #[WhenRole('admin')]
 *         public readonly string $adminPanel = '/admin',
 *     ) {}
 * }
 *
 * $user = (object)['role' => 'admin'];
 * $dto = new UserDto('John');
 * $dto->withContext(['user' => $user])->toArray();
 * ```
 *
 * @example With context (multiple roles)
 * ```php
 * #[WhenRole(['admin', 'moderator'])]
 * public readonly string $moderationPanel = '/moderation';
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenRole implements ConditionalProperty
{
    /** @var array<string> */
    private readonly array $roles;

    /** @param string|array<string> $role Role(s) to check */
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

            // Check if user has hasRole method (Spatie Laravel Permission, etc.)
            if (is_object($user) && method_exists($user, 'hasRole')) {
                foreach ($this->roles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
                return false;
            }

            // Check if user has hasAnyRole method
            if (is_object($user) && method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole($this->roles);
            }

            // Check if user has role property (string)
            if (is_object($user) && isset($user->role)) {
                return in_array($user->role, $this->roles, true);
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

            // Default to false if user doesn't have role info
            return false;
        }

        // Fall back to Laravel Auth if available
        if (class_exists('Illuminate\Support\Facades\Auth')) {
            try {
                $user = Auth::user();

                if (null === $user) {
                    return false;
                }

                // Same checks as above
                if (method_exists($user, 'hasRole')) {
                    foreach ($this->roles as $role) {
                        if ($user->hasRole($role)) {
                            return true;
                        }
                    }
                    return false;
                }

                if (method_exists($user, 'hasAnyRole')) {
                    return $user->hasAnyRole($this->roles);
                }

                if (property_exists($user, 'role') && null !== $user->role) {
                    return in_array($user->role, $this->roles, true);
                }

                if (property_exists($user, 'roles') && null !== $user->roles) {
                    $userRoles = is_array($user->roles) ? $user->roles : [$user->roles];
                    foreach ($this->roles as $role) {
                        if (in_array($role, $userRoles, true)) {
                            return true;
                        }
                    }
                    return false;
                }

                return false;
            } catch (Throwable) {
                // Laravel not properly initialized, treat as no role
                return false;
            }
        }

        // Default to false if no context and no Laravel
        return false;
    }
}
