<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use Throwable;

/**
 * Handles exception collection for a single DataMapper operation.
 *
 * Unlike the static MapperExceptions class, this handler is instantiated per mapping operation,
 * allowing exceptions to be tracked per Result/Mapping instead of globally.
 */
class DataMapperExceptionHandler
{
    /** @var array<int, Throwable> */
    private array $exceptions = [];

    /**
     * @param bool $collectExceptions If true, exceptions are collected; if false, they are thrown immediately
     * @param bool $throwOnError If true, collected exceptions are thrown at the end of the operation
     */
    public function __construct(
        private bool $collectExceptions = true,
        private bool $throwOnError = false,
    ) {
    }

    /**
     * Handle an exception based on current settings.
     *
     * If collectExceptions is true, the exception is added to the collection.
     * If collectExceptions is false, the exception is thrown immediately.
     *
     * @throws Throwable
     */
    public function handleException(Throwable $exception): void
    {
        if ($this->collectExceptions) {
            $this->addException($exception);

            return;
        }

        throw $exception;
    }

    /** Add an exception to the collection. */
    public function addException(Throwable $exception): void
    {
        $this->exceptions[] = $exception;
    }

    /** Check if any exceptions have been collected. */
    public function hasExceptions(): bool
    {
        return [] !== $this->exceptions;
    }

    /**
     * Get all collected exceptions.
     *
     * @return array<int, Throwable>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /** Get the last collected exception or null if none. */
    public function getLastException(): ?Throwable
    {
        if ([] === $this->exceptions) {
            return null;
        }

        return $this->exceptions[array_key_last($this->exceptions)];
    }

    /** Get the number of collected exceptions. */
    public function getExceptionCount(): int
    {
        return count($this->exceptions);
    }

    /** Clear all collected exceptions. */
    public function clearExceptions(): void
    {
        $this->exceptions = [];
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
    public function throwCollectedExceptions(): void
    {
        if (!$this->hasExceptions()) {
            return;
        }

        // Store exceptions before clearing
        $exceptionsToThrow = $this->exceptions;

        // Clear exceptions before throwing
        $this->clearExceptions();

        // If only one exception, throw it directly
        if (1 === count($exceptionsToThrow)) {
            throw $exceptionsToThrow[0];
        }

        // Multiple exceptions: wrap in CollectedExceptionsException
        throw new CollectedExceptionsException($exceptionsToThrow);
    }

    /**
     * Throw the last collected exception if any.
     *
     * @throws Throwable
     */
    public function throwLastException(): void
    {
        $lastException = $this->getLastException();
        if ($lastException instanceof Throwable) {
            throw $lastException;
        }
    }

    /** Check if exceptions should be thrown at the end of the operation. */
    public function shouldThrowOnError(): bool
    {
        return $this->throwOnError;
    }

    /** Set whether exceptions should be thrown at the end of the operation. */
    public function setThrowOnError(bool $throwOnError): self
    {
        $this->throwOnError = $throwOnError;

        return $this;
    }

    /** Check if exceptions are being collected. */
    public function isCollectingExceptions(): bool
    {
        return $this->collectExceptions;
    }

    /** Set whether exceptions should be collected. */
    public function setCollectExceptions(bool $collectExceptions): self
    {
        $this->collectExceptions = $collectExceptions;

        return $this;
    }
}
