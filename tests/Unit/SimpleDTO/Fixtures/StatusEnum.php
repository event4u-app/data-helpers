<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDTO\Fixtures;

/**
 * Test fixture: Backed String Enum.
 */
enum StatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
