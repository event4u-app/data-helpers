<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is required if any of the specified fields are present.
 *
 * Example:
 * ```php
 * class ContactDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly ?string $phone = null,
 *         public readonly ?string $email = null,
 *         
 *         #[RequiredWith(['phone', 'email'])]
 *         public readonly ?string $contactPreference = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredWith implements ValidationRule
{
    /**
     * @param array<string> $fields Field names that trigger requirement
     */
    public function __construct(
        public readonly array $fields,
    ) {}

    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        return 'required_with:' . implode(',', $this->fields);
    }

    /**
     * Get validation error message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $fields = implode(', ', $this->fields);
        return "The attribute field is required when {$fields} is present.";
    }
}

