<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;

/**
 * Validation attribute: Value must exist (custom callback validation).
 *
 * This attribute allows custom validation logic via callback.
 * Use this when you need framework-agnostic database validation.
 *
 * Examples:
 * ```php
 * use App\Models\User;
 * use App\Models\Product;
 *
 * class OrderDto extends LiteDto
 * {
 *     public function __construct(
 *         // Check if user exists by ID
 *         #[ExistsCallback(fn($value) => User::where('id', $value)->exists())]
 *         public readonly int $userId,
 *
 *         // Check if user exists by email
 *         #[ExistsCallback(fn($value) => User::where('email', $value)->exists())]
 *         public readonly string $userEmail,
 *
 *         // Check if product exists and is active
 *         #[ExistsCallback(fn($value) => Product::where('id', $value)->where('active', true)->exists())]
 *         public readonly int $productId,
 *     ) {}
 * }
 * ```
 *
 * Example with Doctrine:
 * ```php
 * class OrderDto extends LiteDto
 * {
 *     public function __construct(
 *         #[ExistsCallback(fn($value) => $entityManager->getRepository(User::class)->find($value) !== null)]
 *         public readonly int $userId,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ExistsCallback implements ValidationAttribute
{
    /** @param callable $callback Callback to check existence: fn(mixed $value): bool */
    public function __construct(
        public readonly mixed $callback,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation for null values (use Required attribute to enforce non-null)
        if (null === $value) {
            return true;
        }

        if (!is_callable($this->callback)) {
            return true;
        }

        return (bool)($this->callback)($value);
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The selected %s is invalid.', $propertyName);
    }
}
