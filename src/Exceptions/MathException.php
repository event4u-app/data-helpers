<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use Exception;
use Throwable;

class MathException extends Exception
{
    /** @param array<int|string, mixed> $data */
    public function __construct(
        protected Throwable $throwable,
        protected array $data,
    ) {
        parent::__construct(
            $this->throwable->getMessage(),
            $this->throwable->getCode(),
            $this->throwable
        );
    }

    /** @return array<int|string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }
}
