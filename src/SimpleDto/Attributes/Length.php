<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validate length (maximum or range).
 *
 * One parameter: Maximum length (0 to $max)
 * Two parameters: Length range ($min to $max)
 *
 * For strings: character length
 * For numbers: number of digits
 * For arrays: number of items
 *
 * Example:
 *   #[Length(10)]
 *   public readonly string $name;  // 0-10 characters (varchar(10))
 *
 *   #[Length(1, 3)]
 *   public readonly int $code;  // 1-3 digits (int(3))
 *
 *   #[Length(10)]
 *   public readonly int $status;  // 0-10 digits
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Length implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public readonly int $min;
    public readonly int $max;

    /**
     * @param int $maxOrMin The max length, if $max is null
     * @param null|int $max If this is set, $maxOrMin is the min length
     */
    public function __construct(
        int $maxOrMin,
        ?int $max = null,
        public readonly ?string $message = null
    ) {
        if (null === $max) {
            // Single parameter: 0 to $maxOrMin
            $this->min = 0;
            $this->max = $maxOrMin;
        } else {
            // Two parameters: $maxOrMin to $max
            $this->min = $maxOrMin;
            $this->max = $max;
        }

        if (0 > $this->min) {
            throw new InvalidArgumentException('Length min must be at least 0');
        }

        if (1 > $this->max) {
            throw new InvalidArgumentException('Length max must be at least 1');
        }

        if ($this->min > $this->max) {
            throw new InvalidArgumentException('Length min must be less than or equal to max');
        }
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        // Skip validation if value is null (use Required for null checks)
        if (null === $value) {
            return true;
        }

        $length = 0;

        // For strings: check length
        if (is_string($value)) {
            $length = mb_strlen($value);
        } elseif (is_array($value)) {
            // For arrays: check count
            $length = count($value);
        } elseif (is_numeric($value)) {
            // For numbers: check number of digits
            $stringValue = (string)abs((int)$value);
            $length = strlen($stringValue);
        } else {
            return false;
        }

        return $length >= $this->min && $length <= $this->max;
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        if (0 === $this->min) {
            return sprintf('The %s must be at most %s characters/digits.', $propertyName, $this->max);
        }

        return sprintf('The %s must be between %s and %s characters/digits.', $propertyName, $this->min, $this->max);
    }

    public function rule(): string
    {
        if (0 === $this->min) {
            return 'max:' . $this->max;
        }

        return 'between:' . $this->min . ',' . $this->max;
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        /** @var int<1, max> $max */
        $max = $this->max;

        /** @var int<0, max> $min */
        $min = $this->min;

        return new Assert\Length(
            min: $min,
            max: $max,
            minMessage: $this->message,
            maxMessage: $this->message,
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
