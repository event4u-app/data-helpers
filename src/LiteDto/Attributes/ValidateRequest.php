<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Attribute to enable automatic request validation for LiteDto.
 *
 * When applied to a LiteDto class, it enables automatic validation
 * when the Dto is created from request data in Laravel or Symfony.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\LiteDto\LiteDto;
 * use event4u\DataHelpers\LiteDto\Attributes\ValidateRequest;
 * use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
 * use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
 * use event4u\DataHelpers\LiteDto\Attributes\Validation\Min;
 *
 * #[ValidateRequest(throw: true)]
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[Required]
 *         #[Email]
 *         public readonly string $email,
 *
 *         #[Required]
 *         #[Min(3)]
 *         public readonly string $name,
 *     ) {}
 * }
 *
 * // In Laravel controller
 * public function store(UserDto $dto)
 * {
 *     // $dto is automatically validated
 *     // If validation fails, exception is thrown (throw: true)
 * }
 *
 * // In Symfony controller
 * #[Route('/users', methods: ['POST'])]
 * public function create(UserDto $dto): Response
 * {
 *     // $dto is automatically validated
 *     // If validation fails, exception is thrown (throw: true)
 * }
 * ```
 *
 * @package event4u\DataHelpers\LiteDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class ValidateRequest
{
    /**
     * @param bool $throw Whether to throw exception on validation failure
     * @param bool $auto Whether to automatically validate on from()
     * @param bool $stopOnFirstFailure Stop validation on first failure
     * @param array<string> $only Only validate these fields
     * @param array<string> $except Exclude these fields from validation
     * @param array<string> $groups Validation groups to apply
     */
    public function __construct(
        public bool $throw = true,
        public bool $auto = false,
        public bool $stopOnFirstFailure = false,
        public array $only = [],
        public array $except = [],
        public array $groups = [],
    ) {}
}
