<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Attribute to provide custom validation error messages for a property.
 *
 * This attribute can be used alongside validation attributes to provide
 * custom error messages for specific validation rules.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Required]
 *         #[Email]
 *         #[WithMessage([
 *             'required' => 'Please provide your email address',
 *             'email' => 'The email format is invalid'
 *         ])]
 *         public readonly string $email,
 *
 *         #[Required]
 *         #[Min(8)]
 *         #[WithMessage([
 *             'required' => 'Password is required',
 *             'min' => 'Password must be at least 8 characters'
 *         ])]
 *         public readonly string $password,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WithMessage
{
    /** @param array<string, string> $messages Custom error messages keyed by rule name */
    public function __construct(
        public readonly array $messages,
    ) {}

    /**
     * Get custom message for a specific rule.
     *
     * @param string $rule Rule name (e.g., 'required', 'email', 'min')
     */
    public function getMessage(string $rule): ?string
    {
        return $this->messages[$rule] ?? null;
    }

    /**
     * Get all custom messages.
     *
     * @return array<string, string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
