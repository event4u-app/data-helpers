<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Helpers;

use DivisionByZeroError;
use event4u\DataHelpers\Exceptions\MathException;
use InvalidArgumentException;
use Throwable;

class MathHelper
{
    public const DEFAULT_SCALE = 16;
    public const MAXIMUM_PHP_SCALE = 53;
    public const THROW_EXCEPTION_AT_DIVISION_BY_ZERO = true;

    protected static bool $convertMalformedInputToZero = false;

    public static function setConvertMalformedInputToZero(bool $value): void
    {
        self::$convertMalformedInputToZero = $value;
    }

    public static function getConvertMalformedInputToZero(): bool
    {
        return self::$convertMalformedInputToZero;
    }

    /** @throws MathException */
    public static function add(
        null|float|int|string $num1,
        null|float|int|string $num2,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $num1 = self::convertToTrimmedString($num1);
        $num2 = self::convertToTrimmedString($num2);

        try {
            return (float)bcadd($num1, $num2, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'num1' => $num1,
                'num2' => $num2,
                'scale' => $scale,
            ]);
        }
    }

    /** @throws MathException */
    public static function subtract(
        null|float|int|string $num1,
        null|float|int|string $num2,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $num1 = self::convertToTrimmedString($num1);
        $num2 = self::convertToTrimmedString($num2);

        try {
            return (float)bcsub($num1, $num2, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'num1' => $num1,
                'num2' => $num2,
                'scale' => $scale,
            ]);
        }
    }

    /** @throws MathException */
    public static function divide(
        null|float|int|string $num1,
        null|float|int|string $num2,
        int $scale = self::DEFAULT_SCALE,
        bool $throwExceptionAtDivisionByZero = self::THROW_EXCEPTION_AT_DIVISION_BY_ZERO,
    ): float {
        $num1 = self::convertToTrimmedString($num1);
        $num2 = self::convertToTrimmedString($num2);

        // Check for division by zero
        if ('0' === $num2) {
            if ($throwExceptionAtDivisionByZero) {
                throw new MathException(
                    new DivisionByZeroError('Division by zero'),
                    [
                        'method' => __METHOD__,
                        'num1' => $num1,
                        'num2' => $num2,
                        'scale' => $scale,
                        'throwExceptionAtDivisionByZero' => $throwExceptionAtDivisionByZero,
                    ]
                );
            }

            return 0.0;
        }

        try {
            return (float)bcdiv($num1, $num2, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'num1' => $num1,
                'num2' => $num2,
                'scale' => $scale,
                'throwExceptionAtDivisionByZero' => $throwExceptionAtDivisionByZero,
            ]);
        }
    }

    /** @throws MathException */
    public static function multiply(
        null|float|int|string $num1,
        null|float|int|string $num2,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $num1 = self::convertToTrimmedString($num1);
        $num2 = self::convertToTrimmedString($num2);

        try {
            return (float)bcmul($num1, $num2, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'num1' => $num1,
                'num2' => $num2,
                'scale' => $scale,
            ]);
        }
    }

    /** @throws MathException */
    public static function modulo(
        null|float|int|string $num1,
        null|float|int|string $num2,
        int $scale = self::DEFAULT_SCALE,
        bool $throwExceptionAtDivisionByZero = self::THROW_EXCEPTION_AT_DIVISION_BY_ZERO,
    ): float {
        $num1 = self::convertToTrimmedString($num1);
        $num2 = self::convertToTrimmedString($num2);

        if (
            !$throwExceptionAtDivisionByZero
            && (
                (empty($num1) || empty($num2))
                || (!is_numeric($num1) || !is_numeric($num2))
            )
        ) {
            return 0;
        }

        try {
            return (float)bcmod($num1, $num2, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'num1' => $num1,
                'num2' => $num2,
                'scale' => $scale,
                'throwExceptionAtDivisionByZero' => $throwExceptionAtDivisionByZero,
            ]);
        }
    }

    /** @throws MathException */
    public static function powerOf(
        null|float|int|string $number,
        null|float|int|string $exponent,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $number = self::convertToTrimmedString($number);
        $exponent = self::convertToTrimmedString($exponent);

        try {
            return (float)bcpow($number, $exponent, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'number' => $number,
                'exponent' => $exponent,
                'scale' => $scale,
            ]);
        }
    }

    /** @throws MathException */
    public static function squareRoot(
        null|float|int|string $number,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $number = self::convertToTrimmedString($number);

        try {
            return (float)bcsqrt($number, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'number' => $number,
                'scale' => $scale,
            ]);
        }
    }

    /** @throws MathException */
    public static function compare(
        null|float|int|string $num1,
        null|float|int|string $num2,
        int $scale = self::DEFAULT_SCALE,
        bool $trimNumbers = false,
    ): int {
        // Always convert to numeric-string for bccomp
        $num1 = self::convertToTrimmedString($num1);
        $num2 = self::convertToTrimmedString($num2);

        try {
            return bccomp($num1, $num2, self::checkMaximumScale($scale));
        } catch (Throwable $throwable) {
            throw new MathException($throwable, [
                'method' => __METHOD__,
                'num1' => $num1,
                'num2' => $num2,
                'scale' => $scale,
                'trimNumbers' => $trimNumbers,
            ]);
        }
    }

    /** @param array<int|string, null|float|int|string> $values */
    public static function min(
        array $values,
    ): float|int {
        $filtered = self::whereNotNull($values);

        if ([] === $filtered) {
            return 0;
        }

        $min = min($filtered);

        return str_contains((string)$min, '.') ? (float)$min : (int)$min;
    }

    /** @param array<int|string, null|float|int|string> $values */
    public static function max(
        array $values,
    ): float|int {
        $filtered = self::whereNotNull($values);

        if ([] === $filtered) {
            return 0;
        }

        $max = max($filtered);

        return str_contains((string)$max, '.') ? (float)$max : (int)$max;
    }

    /**
     * @param array<int|string, null|float|int|string> $values
     * @throws MathException
     */
    public static function sum(
        array $values,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $sum = 0.0;

        foreach (self::whereNotNull($values) as $n) {
            $sum = self::add($sum, $n, $scale);
        }

        return $sum;
    }

    /**
     * @param array<int|string, null|float|int|string> $values
     * @throws MathException
     */
    public static function subSum(
        array $values,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $sum = 0.0;

        foreach (self::whereNotNull($values) as $n) {
            $sum = self::subtract($sum, $n, $scale);
        }

        return $sum;
    }

    /**
     * @param array<int|string, null|float|int|string> $values
     * @throws MathException
     */
    public static function average(
        array $values,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $values = self::whereNotNull($values);

        if ([] === $values) {
            return 0.0;
        }

        return self::divide(
            array_sum($values),
            count($values),
            self::checkMaximumScale($scale)
        );
    }

    /** @throws MathException */
    public static function convertMinutesToDecimalHours(
        null|float|int|string $minutes,
        int $numberFormatDecimals = 0,
        ?string $numberFormatDecimalSeparator = '.',
        ?string $numberFormatThousandsSeparator = ',',
    ): string {
        return number_format(
            self::divide($minutes, 60, self::DEFAULT_SCALE),
            $numberFormatDecimals,
            $numberFormatDecimalSeparator,
            $numberFormatThousandsSeparator
        );
    }

    /** @throws MathException */
    public static function convertMinutesToDecimalHoursAsFloat(
        null|float|int|string $minutes,
        int $numberFormatDecimals = 0,
    ): float {
        return (float)self::convertMinutesToDecimalHours($minutes, $numberFormatDecimals, '.', '');
    }

    /** @throws MathException */
    public static function convertMinutesToDecimalHoursRounded(
        null|float|int|string $minutes,
        int $roundingPrecision = 0,
        int $roundingMode = PHP_ROUND_HALF_UP,
    ): float {
        return round(
            self::divide($minutes, 60, self::DEFAULT_SCALE),
            $roundingPrecision,
            (1 <= $roundingMode && 4 >= $roundingMode) ? $roundingMode : PHP_ROUND_HALF_UP,
        );
    }

    /** @throws MathException */
    public static function convertMinutesRoundedToDecimalHours(
        null|float|int|string $minutes,
        int $roundingPrecision = 0,
        int $roundingMode = PHP_ROUND_HALF_UP,
        int $numberFormatDecimals = 0,
        ?string $numberFormatDecimalSeparator = '.',
        ?string $numberFormatThousandsSeparator = ',',
    ): string {
        return number_format(
            round(
                self::divide($minutes, 60, self::DEFAULT_SCALE),
                $roundingPrecision,
                (1 <= $roundingMode && 4 >= $roundingMode) ? $roundingMode : PHP_ROUND_HALF_UP,
            ),
            $numberFormatDecimals,
            $numberFormatDecimalSeparator,
            $numberFormatThousandsSeparator
        );
    }

    /** @throws MathException */
    public static function convertDecimalHoursToSeconds(
        null|float|int|string $hours,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        return self::multiply(
            self::multiply(
                $hours,
                60,
                self::checkMaximumScale($scale)
            ),
            60,
            self::checkMaximumScale($scale)
        );
    }

    /** @throws MathException */
    public static function convertDecimalHoursToSecondsRounded(
        null|float|int|string $hours,
        int $precision = 0,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        return round(
            self::convertDecimalHoursToSeconds($hours, self::checkMaximumScale($scale)),
            $precision
        );
    }

    /**
     * Convert minutes to HH:MM format.
     *
     * @throws MathException
     */
    public static function convertMinutesToHourMinutes(
        null|float|int|string $minutes,
    ): string {
        $hours = floor(self::divide($minutes, 60));
        $remainingMinutes = self::modulo($minutes, 60);

        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }

    /**
     * Convert HH:MM format to minutes.
     *
     * @throws MathException
     */
    public static function convertHoursMinutesToMinutes(
        string $timeString,
    ): int {
        $parts = explode(':', $timeString);
        $hours = (int)$parts[0];
        $minutes = (int)($parts[1] ?? 0);

        return (int)ceil(
            self::add(
                self::multiply($hours, 60),
                $minutes
            )
        );
    }

    /**
     * @return numeric-string
     * @throws MathException
     */
    protected static function convertToTrimmedString(null|float|int|string $value): string
    {
        if (null === $value || '' === $value) {
            return '0';
        }

        if (!is_string($value)) {
            $value = (string)$value;
        }

        $value = trim($value);

        if (!is_numeric($value)) {
            if (self::$convertMalformedInputToZero) {
                return '0';
            }

            throw new MathException(
                new InvalidArgumentException("Value is not numeric: {$value}"),
                ['value' => $value]
            );
        }

        return self::convertScientificToDecimal($value);
    }

    /**
     * @param numeric-string $value
     * @return numeric-string
     */
    protected static function convertScientificToDecimal(
        string $value,
        int $scale = self::DEFAULT_SCALE,
    ): string {
        if (str_contains(strtolower($value), 'e')) {
            // we need to convert scientific notification (e.g. "3.8773213097356E-12") to float
            $formatted = sprintf('%.' . self::checkMaximumScale($scale) . 'f', $value);
            // Ensure it's a numeric-string
            return is_numeric($formatted) ? $formatted : '0';
        }

        return $value;
    }

    protected static function checkMaximumScale(int $scale): int
    {
        return min($scale, self::MAXIMUM_PHP_SCALE);
    }

    /**
     * @param array<int|string, null|float|int|string> $values
     * @throws MathException
     */
    public static function product(
        array $values,
        int $scale = self::DEFAULT_SCALE,
    ): float {
        $product = 1;

        foreach ($values as $value) {
            if (null === $value) {
                continue;
            }

            $product = self::multiply($product, $value, $scale);
        }

        return $product;
    }

    /**
     * Filter null values from array.
     *
     * @template T
     * @param array<int|string, T|null> $values
     * @return array<int|string, T>
     */
    protected static function whereNotNull(array $values): array
    {
        /** @var array<int|string, T> */
        return array_filter($values, fn($value): bool => null !== $value);
    }
}
