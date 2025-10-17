<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDTO\Fixtures;

/**
 * Test fixture: Backed Integer Enum.
 */
enum RoleEnum: int
{
    case GUEST = 0;
    case USER = 1;
    case MODERATOR = 2;
    case ADMIN = 3;
}

