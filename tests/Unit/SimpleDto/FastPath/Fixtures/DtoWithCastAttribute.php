<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;

/**
 * DTO with DataCollectionOf attribute (which uses casting).
 * Should NOT be eligible for FastPath.
 */
class DtoWithCastAttribute extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        #[DataCollectionOf(SimpleDto::class)]
        public readonly mixed $items = null,
    ) {}
}
