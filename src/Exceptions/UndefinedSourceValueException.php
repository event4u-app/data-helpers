<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a source value path does not exist.
 */
class UndefinedSourceValueException extends RuntimeException
{
    public function __construct(
        private readonly string $path,
        private readonly mixed $source,
    ) {
        parent::__construct(
            sprintf(
                'Source value at path "%s" is undefined',
                $path
            )
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSource(): mixed
    {
        return $this->source;
    }
}

