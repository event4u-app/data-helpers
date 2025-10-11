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
        $message = sprintf(
            'Collected %d exception%s during mapping',
            $count,
            1 === $count ? '' : 's'
        );

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

