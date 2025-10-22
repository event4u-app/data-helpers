<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is required if any of the specified fields are NOT present.
 *
 * Example:
 * ```php
 * class ContactDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly ?string $phone = null,
 *         
 *         #[RequiredWithout(['phone'])]
 *         public readonly ?string $email = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequiredWithout implements ValidationRule
{
    /** @param array<string> $fields Field names that trigger requirement when absent */
    public function __construct(
        public readonly array $fields,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'required_without:' . implode(',', $this->fields);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $fields = implode(', ', $this->fields);
        return sprintf('The attribute field is required when %s is not present.', $fields);
    }
}
