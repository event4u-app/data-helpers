<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DataCollection;

/**
 * DTO with DataCollection property.
 * Should be eligible for FastPath (DataCollection is just a property).
 */
class DtoWithDataCollection extends SimpleDto
{
    /** @param DataCollection<SimpleDto>|null $items */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?DataCollection $items = null,
    ) {}
}
