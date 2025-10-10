<?php

namespace event4u\DataHelpers\Traits;

use Carbon\Carbon;
use Exception;
use InvalidArgumentException;

/**
 * Conditional Carbon support trait.
 *
 * This file defines the trait based on whether Carbon is available.
 */

if (class_exists('Carbon\Carbon')) {
    /**
     * Carbon support trait (Carbon is available).
     */
    trait EnvHelperCarbonTrait
    {
        /**
         * Get an environment variable as Carbon instance.
         *
         * @return \Carbon\Carbon
         * @throws InvalidArgumentException If the value cannot be parsed as a date/time
         */
        public static function carbon(string $key, mixed $default = null): Carbon
        {
            $value = self::get($key, $default);

            if (!is_string($value) && !is_int($value) && !is_float($value) && null !== $value) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Configuration value for key [%s] must be a string, int, float or null for Carbon parsing, %s given.',
                        $key,
                        get_debug_type($value)
                    )
                );
            }

            try {
                /** @var string|int|float|null $value */
                $carbon = new Carbon($value);
            } catch (Exception $exception) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Configuration value for key [%s] must be date/datetime. Exception: %s',
                        $key,
                        $exception->getMessage(),
                    ),
                    $exception->getCode(),
                    $exception
                );
            }

            return $carbon;
        }

        /** Check if Carbon support is available. */
        public static function hasCarbonSupport(): bool
        {
            return true;
        }
    }
} else {
    /**
     * Carbon support trait (Carbon is NOT available).
     */
    trait EnvHelperCarbonTrait
    {
        /**
         * Get an environment variable as Carbon instance.
         *
         * @throws InvalidArgumentException Always throws because Carbon is not available
         */
        public static function carbon(string $key, mixed $default = null): void
        {
            throw new InvalidArgumentException(
                'Carbon support is not available. Install nesbot/carbon to use EnvHelper::carbon()'
            );
        }

        /** Check if Carbon support is available. */
        public static function hasCarbonSupport(): bool
        {
            return false;
        }
    }
}

