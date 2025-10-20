<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Attribute to specify which validation groups a rule belongs to.
 *
 * This allows different validation rules for different scenarios (e.g., create vs update).
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Required]
 *         #[RuleGroup(['create', 'update'])]
 *         public readonly string $name,
 *         
 *         #[Required]
 *         #[RuleGroup(['create'])]  // Only required when creating
 *         public readonly string $password,
 *         
 *         #[Sometimes]
 *         #[Min(8)]
 *         #[RuleGroup(['update'])]  // Only validated when updating
 *         public readonly ?string $newPassword = null,
 *     ) {}
 * }
 * 
 * // Usage:
 * $user = UserDTO::validateAndCreate($data, groups: ['create']);
 * $user = UserDTO::validateAndCreate($data, groups: ['update']);
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class RuleGroup
{
    /** @param array<string> $groups Validation groups this rule belongs to */
    public function __construct(
        public readonly array $groups,
    ) {}

    /**
     * Check if this rule belongs to a specific group.
     *
     * @param string $group Group name to check
     */
    public function belongsToGroup(string $group): bool
    {
        return in_array($group, $this->groups, true);
    }

    /**
     * Check if this rule belongs to any of the specified groups.
     *
     * @param array<string> $groups Groups to check
     */
    public function belongsToAnyGroup(array $groups): bool
    {
        return array_intersect($this->groups, $groups) !== [];
    }

    /**
     * Get all groups.
     *
     * @return array<string>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}

