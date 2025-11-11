<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

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
class File implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    /**
     * @param int|null $maxSize Maximum file size in kilobytes
     * @param int|null $minSize Minimum file size in kilobytes
     */
    public function __construct(
        public readonly ?int $maxSize = null,
        public readonly ?int $minSize = null,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value) {
            return true; // Null values are handled by Required attribute
        }

        // Check if it's an uploaded file (Laravel UploadedFile or Symfony UploadedFile)
        if (is_object($value)) {
            $className = $value::class;
            if (str_contains($className, 'UploadedFile')) {
                // Check file size if specified
                if (null !== $this->maxSize && method_exists($value, 'getSize')) {
                    $sizeInKb = $value->getSize() / 1024;
                    if ($sizeInKb > $this->maxSize) {
                        return false;
                    }
                }

                if (null !== $this->minSize && method_exists($value, 'getSize')) {
                    $sizeInKb = $value->getSize() / 1024;
                    if ($sizeInKb < $this->minSize) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s must be a file.', $propertyName);
    }

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
    public function constraint(): object
    {
        // Symfony uses bytes, Laravel uses kilobytes
        $options = [];
        if (null !== $this->maxSize && 0 < $this->maxSize) {
            $options['maxSize'] = $this->maxSize * 1024;
        }

        return $this->createConstraint(
            "\\Symfony\\Component\\Validator\\Constraints\\File",
            $options
        );
    }
}
