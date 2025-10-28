<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

/**
 * DTO with mapping attribute (#[MapFrom]).
 * Should NOT be eligible for FastPath.
 */
class DtoWithMappingAttribute extends SimpleDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly ?string $name = null,
    ) {}
}
