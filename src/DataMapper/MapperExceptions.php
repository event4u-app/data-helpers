<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use event4u\DataHelpers\Exceptions\ConversionException;
use event4u\DataHelpers\Exceptions\InvalidMappingException;
use event4u\DataHelpers\Exceptions\UndefinedSourceValueException;
use event4u\DataHelpers\Exceptions\UndefinedTargetValueException;
use RuntimeException;
use Throwable;

/**
 * Manages exception handling for DataMapper operations.
 *
 * This class encapsulates all exception-related logic including:
 * - Collecting vs throwing exceptions
 * - Handling undefined source/target values
 * - Managing collected exceptions
 */
class MapperExceptions
{
    /** Master switch to disable all exception handling */
    private static bool $exceptionsEnabled = true;

    private static bool $collectExceptions = true;

    /** @var array<int, Throwable> */
    private static array $exceptions = [];

    private static bool $throwExceptionOnUndefinedSourceValue = false;

    private static bool $throwExceptionOnUndefinedTargetValue = false;

    /** Reset all settings to their default values. */
    public static function reset(): void
    {
        self::$exceptionsEnabled = true;
        self::$collectExceptions = true;
        self::$throwExceptionOnUndefinedSourceValue = false;
        self::$throwExceptionOnUndefinedTargetValue = false;
        self::$exceptions = [];
    }

    /** Enable or disable all exception handling (master switch). */
    public static function setExceptionsEnabled(bool $enabled): void
    {
        self::$exceptionsEnabled = $enabled;
    }

    /** Check whether exception handling is enabled. */
    public static function isExceptionsEnabled(): bool
    {
        return self::$exceptionsEnabled;
    }

    /** Set whether to collect exceptions instead of throwing them immediately. */
    public static function setCollectExceptionsEnabled(bool $enabled): void
    {
        self::$collectExceptions = $enabled;
    }

    /** Check whether exception collection is enabled. */
    public static function isCollectExceptionsEnabled(): bool
    {
        return self::$collectExceptions;
    }

    /** Set whether to throw exception when source value is undefined. */
    public static function setThrowOnUndefinedSourceEnabled(bool $enabled): void
    {
        self::$throwExceptionOnUndefinedSourceValue = $enabled;
    }

    /** Check whether throwing exception on undefined source value is enabled. */
    public static function isThrowOnUndefinedSourceEnabled(): bool
    {
        return self::$throwExceptionOnUndefinedSourceValue;
    }

    /** Set whether to throw exception when target value is undefined. */
    public static function setThrowOnUndefinedTargetEnabled(bool $enabled): void
    {
        self::$throwExceptionOnUndefinedTargetValue = $enabled;
    }

    /** Check whether throwing exception on undefined target value is enabled. */
    public static function isThrowOnUndefinedTargetEnabled(): bool
    {
        return self::$throwExceptionOnUndefinedTargetValue;
    }

    /** Check if there are any collected exceptions. */
    public static function hasExceptions(): bool
    {
        return [] !== self::$exceptions;
    }

    /**
     * Get all collected exceptions.
     *
     * @return array<int, Throwable>
     */
    public static function getExceptions(): array
    {
        return self::$exceptions;
    }

    /** Clear all collected exceptions. */
    public static function clearExceptions(): void
    {
        self::$exceptions = [];
    }

    /** Add an exception to the collection. */
    public static function addException(Throwable $exception): void
    {
        self::$exceptions[] = $exception;
    }

    /**
     * Handle an exception based on current settings.
     *
     * If exceptions are disabled globally, the exception is silently ignored.
     * If collectExceptions is true, the exception is added to the collection.
     * If collectExceptions is false, the exception is thrown immediately.
     *
     * @throws Throwable
     */
    public static function handleException(Throwable $exception): void
    {
        // Master switch: if exceptions are disabled, ignore silently
        if (!self::$exceptionsEnabled) {
            return;
        }

        if (self::$collectExceptions) {
            self::addException($exception);

            return;
        }

        throw $exception;
    }

    /**
     * Handle undefined source value based on current settings.
     *
     * @throws UndefinedSourceValueException
     */
    public static function handleUndefinedSourceValue(string $path, mixed $source): void
    {
        if (!self::$throwExceptionOnUndefinedSourceValue) {
            return;
        }

        $exception = new UndefinedSourceValueException($path, $source);
        self::handleException($exception);
    }

    /**
     * Handle undefined target value based on current settings.
     *
     * @throws UndefinedTargetValueException
     */
    public static function handleUndefinedTargetValue(string $path, mixed $target): void
    {
        if (!self::$throwExceptionOnUndefinedTargetValue) {
            return;
        }

        $exception = new UndefinedTargetValueException($path, $target);
        self::handleException($exception);
    }

    /**
     * Throw all collected exceptions.
     *
     * If only one exception was collected, it is thrown directly.
     * If multiple exceptions were collected, they are wrapped in a CollectedExceptionsException.
     * After throwing, the exception list is cleared.
     *
     * @throws Throwable
     */
    public static function throwCollectedExceptions(): void
    {
        if (!self::hasExceptions()) {
            return;
        }

        // Store exceptions before clearing
        $exceptionsToThrow = self::$exceptions;

        // Clear exceptions before throwing
        self::clearExceptions();

        // If only one exception, throw it directly
        if (1 === count($exceptionsToThrow)) {
            throw $exceptionsToThrow[0];
        }

        // Multiple exceptions: wrap in CollectedExceptionsException
        throw new CollectedExceptionsException($exceptionsToThrow);
    }

    /** Get the last collected exception or null if none. */
    public static function getLastException(): ?Throwable
    {
        if ([] === self::$exceptions) {
            return null;
        }

        return self::$exceptions[array_key_last(self::$exceptions)];
    }

    /** Get the number of collected exceptions. */
    public static function getExceptionCount(): int
    {
        return count(self::$exceptions);
    }

    /**
     * Handle a conversion exception (JSON, XML, etc.).
     *
     * @throws ConversionException
     */
    public static function handleConversionException(string $message): void
    {
        $exception = new ConversionException($message);
        self::handleException($exception);
    }

    /**
     * Handle an invalid mapping exception.
     *
     * @throws InvalidMappingException
     */
    public static function handleInvalidMappingException(string $message): void
    {
        $exception = new InvalidMappingException($message);
        self::handleException($exception);
    }

    /**
     * Handle a runtime exception.
     *
     * @throws RuntimeException
     */
    public static function handleRuntimeException(string $message): void
    {
        $exception = new RuntimeException($message);
        self::handleException($exception);
    }
}
