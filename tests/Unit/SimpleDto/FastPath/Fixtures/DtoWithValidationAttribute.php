<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

/**
 * DTO with validation attributes (#[Required], #[Email]).
 * Should NOT be eligible for FastPath.
 */
class DtoWithValidationAttribute extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly ?string $name = null,
        #[Email]
        public readonly ?string $email = null,
    ) {}
}
