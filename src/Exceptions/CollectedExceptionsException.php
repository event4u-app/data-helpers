<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception that contains multiple collected exceptions.
 */
class CollectedExceptionsException extends RuntimeException
{
    /** @param array<int, Throwable> $exceptions */
    public function __construct(
        private readonly array $exceptions,
    ) {
        $count = count($exceptions);
        $message = 'Collected ' . $count . ' exception' . (1 === $count ? '' : 's') . ' during mapping';

        parent::__construct($message);
    }

    /** @return array<int, Throwable> */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    public function getExceptionCount(): int
    {
        return count($this->exceptions);
    }
}
