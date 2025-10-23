<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use BackedEnum;
use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;
use UnitEnum;
use ValueError;

/**
 * Cast attribute to PHP 8.1+ Enum.
 *
 * Supports:
 * - Backed enums (string/int) - uses from() / tryFrom()
 * - Unit enums - uses case matching by name
 * - Null values
 * - Invalid values (returns null gracefully)
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'status' => 'enum:App\Enums\Status',
 *           'role' => EnumCast::class.':App\Enums\Role',
 *       ];
 *   }
 */
class EnumCast implements CastsAttributes
{
    public function __construct(
        private readonly string $enumClass,
    ) {}

    public function get(mixed $value, array $attributes): ?UnitEnum
    {
        if (null === $value) {
            return null;
        }

        // If already an enum instance, return it
        if ($value instanceof UnitEnum) {
            return $value;
        }

        // Check if enum class exists
        if (!enum_exists($this->enumClass)) {
            return null;
        }

        // Handle backed enums (string/int)
        if (is_subclass_of($this->enumClass, BackedEnum::class)) {
            // Only accept string or int values for backed enums
            if (!is_string($value) && !is_int($value)) {
                return null;
            }

            try {
                /** @var class-string<BackedEnum> $enumClass */
                $enumClass = $this->enumClass;
                /** @var UnitEnum */
                return $enumClass::from($value);
            } catch (ValueError) {
                // Invalid value - return null gracefully
                return null;
            }
        }

        // Handle unit enums (match by name)
        if (is_string($value)) {
            /** @var array<UnitEnum> $cases */
            $cases = $this->enumClass::cases();
            foreach ($cases as $case) {
                if ($case->name === $value) {
                    /** @var UnitEnum */
                    return $case;
                }
            }
        }

        return null;
    }

    public function set(mixed $value, array $attributes): string|int|null
    {
        if (null === $value) {
            return null;
        }

        // If not an enum, return as-is
        if (!$value instanceof UnitEnum) {
            return is_scalar($value) ? (string)$value : null;
        }

        // For backed enums, return the value
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        // For unit enums, return the name
        return $value->name;
    }
}
