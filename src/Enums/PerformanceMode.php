<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Enums;

/**
 * Available performance modes.
 *
 * - fast mode uses simplified parsing without escape sequence handling.
 * - safe mode processes all escape sequences (\n, \t, \", \\, etc.).
 */
enum PerformanceMode: string
{
    case FAST = 'fast';
    case SAFE = 'safe';
}
