<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

/**
 * Exception thrown when a value cannot be converted to the expected type.
 *
 * This exception is used by type-safe getter methods like getInt(), getString(), etc.
 * when the retrieved value doesn't match the expected type or cannot be safely converted.
 */
class TypeMismatchException extends UnsupportedTypeException
{
}
