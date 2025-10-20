<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Attribute to enable automatic request validation.
 *
 * When applied to a DTO class, it enables automatic validation
 * when the DTO is created from request data.
 *
 * Example:
 * ```php
 * #[ValidateRequest(throw: true)]
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Required, Email]
 *         public readonly string $email,
 *
 *         #[Required, Min(3)]
 *         public readonly string $name,
 *     ) {}
 * }
 *
 * // In controller (Laravel/Symfony)
 * public function store(UserDTO $dto)
 * {
 *     // $dto is automatically validated
 *     // If validation fails, exception is thrown (throw: true)
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ValidateRequest
{
    /**
     * @param bool $throw Whether to throw exception on validation failure
     * @param bool $auto Whether to automatically validate on fromArray()
     * @param bool $stopOnFirstFailure Stop validation on first failure
     * @param array<string> $only Only validate these fields
     * @param array<string> $except Exclude these fields from validation
     */
    public function __construct(
        public readonly bool $throw = true,
        public readonly bool $auto = false,
        public readonly bool $stopOnFirstFailure = false,
        public readonly array $only = [],
        public readonly array $except = [],
    ) {}
}

