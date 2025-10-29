<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;

/**
 * DTO with #[Hidden] attribute on property.
 * Should NOT be eligible for FastPath.
 */
class DtoWithHiddenProperty extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        #[Hidden]
        public readonly ?string $secret = null,
    ) {}
}
