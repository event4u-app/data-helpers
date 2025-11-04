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
 *
 * Example 3 - Context Provider (auto-fetches context):
 * ```php
 * #[Visible(
 *     contextProvider: AuthContextProvider::class,
 *     callback: 'canViewEmail'
 * )]
 * public readonly string $email;
 *
 * class AuthContextProvider
 * {
 *     public static function getContext(): mixed
 *     {
 *         return auth()->user();
 *     }
 * }
 *
 * // No need for withVisibilityContext() - context is auto-fetched
 * $user->toArray();
 * ```
 *
 * Example 4 - Laravel Gate:
 * ```php
 * #[Visible(gate: 'view-email')]
 * public readonly string $email;
 * ```
 *
 * Example 5 - Symfony Voter:
 * ```php
 * #[Visible(voter: 'view', attribute: 'email')]
 * public readonly string $email;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Visible
{
    /**
     * @param string|array<string>|null $callback Method name on the Dto or static callback [Class::class, 'method']
     * @param string|null $contextProvider Class name that provides context via static getContext() method
     * @param string|null $gate Laravel Gate name to check
     * @param string|null $voter Symfony Voter attribute to check
     * @param string|null $attribute Additional attribute for voter
     */
    public function __construct(
        public string|array|null $callback = null,
        public ?string $contextProvider = null,
        public ?string $gate = null,
        public ?string $voter = null,
        public ?string $attribute = null,
    ) {}
}
