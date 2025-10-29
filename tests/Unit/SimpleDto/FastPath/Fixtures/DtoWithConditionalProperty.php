<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\WhenValue;

/**
 * DTO with conditional property (#[WhenValue]).
 * Should NOT be eligible for FastPath.
 */
class DtoWithConditionalProperty extends SimpleDto
{
    public function __construct(
        public readonly ?int $age = null,
        #[WhenValue('age', '>=', 18)]
        public readonly ?string $adultContent = null,
    ) {}
}
