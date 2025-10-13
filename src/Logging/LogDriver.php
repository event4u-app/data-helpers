<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging;

/**
 * Available logging drivers.
 */
enum LogDriver: string
{
    case FILESYSTEM = 'filesystem';
    case FRAMEWORK = 'framework';
    case NONE = 'none';
}

