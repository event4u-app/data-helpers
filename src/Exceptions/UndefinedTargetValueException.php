<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a target value path does not exist.
 */
class UndefinedTargetValueException extends RuntimeException
{
    public function __construct(
        private readonly string $path,
        private readonly mixed $target,
    ) {
        parent::__construct('Target path „' . $path . '" does not exist in target data');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTarget(): mixed
    {
        return $this->target;
    }
}
