<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;

/**
 * Exception thrown when data conversion fails (JSON, XML, etc.).
 */
class ConversionException extends RuntimeException
{
}

