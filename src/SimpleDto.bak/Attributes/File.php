<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be a successfully uploaded file.
 *
 * Example:
 * ```php
 * class UploadDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[File]
 *         public readonly mixed $document,
 *
 *         #[File(maxSize: 10240)]  // 10MB
 *         public readonly mixed $largeFile,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class File implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /**
     * @param int|null $maxSize Maximum file size in kilobytes
     * @param int|null $minSize Minimum file size in kilobytes
     */
    public function __construct(
        public readonly ?int $maxSize = null,
        public readonly ?int $minSize = null,
    ) {}

    /**
     * Convert to Laravel validation rule.
     *
     * @return string|array<string>
     */
    public function rule(): string|array
    {
        $rules = ['file'];

        if (null !== $this->maxSize) {
            $rules[] = 'max:' . $this->maxSize;
        }

        if (null !== $this->minSize) {
            $rules[] = 'min:' . $this->minSize;
        }

        return count($rules) === 1 ? $rules[0] : $rules;
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return 'The attribute must be a file.';
    }

    /** Get Symfony constraint. */
    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        if (null !== $this->maxSize && 0 < $this->maxSize) {
            // Symfony uses bytes, Laravel uses kilobytes
            return new Assert\File(maxSize: $this->maxSize * 1024);
        }

        return new Assert\File();
    }
}
