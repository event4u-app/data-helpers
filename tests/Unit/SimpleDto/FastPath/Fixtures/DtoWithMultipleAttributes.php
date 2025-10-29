<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

/**
 * DTO with multiple attributes on one property.
 * Should NOT be eligible for FastPath.
 */
class DtoWithMultipleAttributes extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        #[MapFrom('user_email')]
        public readonly ?string $email = null,
    ) {}
}
