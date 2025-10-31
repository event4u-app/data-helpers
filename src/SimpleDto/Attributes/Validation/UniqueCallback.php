<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;

/**
 * Validation attribute: Value must be unique (custom callback validation).
 *
 * This attribute allows custom validation logic via callback.
 * Use this when you need framework-agnostic database validation.
 * The callback receives the value and all data for conditional checks (e.g., ignoring current record).
 *
 * Examples:
 * ```php
 * use App\Models\User;
 *
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         // Check if email is unique
 *         #[UniqueCallback(fn($value) => !User::where('email', $value)->exists())]
 *         public readonly string $email,
 *
 *         // For updates, ignore current record
 *         #[UniqueCallback(fn($value, $data) => !User::where('email', $value)
 *             ->where('id', '!=', $data['id'] ?? null)
 *             ->exists()
 *         )]
 *         public readonly string $email,
 *
 *         // Check uniqueness with additional conditions
 *         #[UniqueCallback(fn($value, $data) => !User::where('email', $value)
 *             ->where('tenant_id', $data['tenant_id'])
 *             ->where('id', '!=', $data['id'] ?? null)
 *             ->exists()
 *         )]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 *
 * Example with Doctrine:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[UniqueCallback(fn($value, $data) => {
 *             $qb = $entityManager->createQueryBuilder();
 *             $qb->select('COUNT(u.id)')
 *                ->from(User::class, 'u')
 *                ->where('u.email = :email')
 *                ->setParameter('email', $value);
 *
 *             if (isset($data['id'])) {
 *                 $qb->andWhere('u.id != :id')
 *                    ->setParameter('id', $data['id']);
 *             }
 *
 *             return $qb->getQuery()->getSingleScalarResult() === 0;
 *         })]
 *         public readonly string $email,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class UniqueCallback implements ConditionalValidationAttribute
{
    /** @param callable $callback Callback to check uniqueness: fn(mixed $value, array $allData): bool */
    public function __construct(
        public readonly mixed $callback,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // This should not be called directly - use validateConditional instead
        return true;
    }

    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        if (!is_callable($this->callback)) {
            return true;
        }

        return (bool)($this->callback)($value, $allData);
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s has already been taken.', $propertyName);
    }
}
