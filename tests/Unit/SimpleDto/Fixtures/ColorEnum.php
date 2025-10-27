<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\Fixtures;

/**
 * Test fixture: Unit Enum (no backing value).
 */
enum ColorEnum
{
    case RED;
    case GREEN;
    case BLUE;
    case YELLOW;
}
