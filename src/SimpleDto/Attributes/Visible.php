<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Mark a property as conditionally visible based on a callback.
 *
 * The callback receives the Dto instance and optional context,
 * and should return true if the property should be visible.
 *
 * Example 1 - Instance method callback:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     #[Visible(callback: 'canViewEmail')]
 *     public readonly string $email;
 *
 *     private function canViewEmail(mixed $context): bool
 *     {
 *         return $context?->role === 'admin';
 *     }
 * }
 *
 * $user->withVisibilityContext($currentUser)->toArray();
 * ```
 *
 * Example 2 - Static callback:
 * ```php
 * #[Visible(callback: [PermissionChecker::class, 'canViewEmail'])]
 * public readonly string $email;
 *
 * class PermissionChecker
 * {
 *     public static function canViewEmail(mixed $dto, mixed $context): bool
 *     {
 *         return $context?->role === 'admin';
 *     }
 * }
 *
 * $user->withVisibilityContext($currentUser)->toArray();
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Visible
{
    /**
     * @param string|array<string>|null $callback Method name on the Dto or static callback [Class::class, 'method']
     * @param string|null $contextProvider Class name that provides context via static getContext() method
     */
    public function __construct(
        public string|array|null $callback = null,
        public ?string $contextProvider = null,
    ) {}
}
