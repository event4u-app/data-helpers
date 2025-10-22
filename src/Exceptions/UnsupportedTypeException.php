<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when an unsupported data type is encountered.
 */
class UnsupportedTypeException extends InvalidArgumentException
{
}
