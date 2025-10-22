<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: File must have one of the given MIME types (by actual MIME type).
 *
 * More strict than Mimes - checks actual MIME type, not just extension.
 *
 * Example:
 * ```php
 * class DocumentDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[MimeTypes(['application/pdf', 'application/msword'])]
 *         public readonly mixed $document,
 *
 *         #[MimeTypes(['image/jpeg', 'image/png', 'image/gif'])]
 *         public readonly mixed $image,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MimeTypes implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /** @param array<string> $types Allowed MIME types */
    public function __construct(
        public readonly array $types,
    ) {}

    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'mimetypes:' . implode(',', $this->types);
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        $types = implode(', ', $this->types);
        return sprintf('The attribute must be a file of type: %s.', $types);
    }

    /** Get Symfony constraint. */
    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\File(mimeTypes: $this->types);
    }
}
