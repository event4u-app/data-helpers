<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\Support\Optional;

/**
 * DTO with Optional wrapper type.
 * Should NOT be eligible for FastPath.
 */
class DtoWithOptionalType extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly ?string $name = null,
        /** @phpstan-ignore-next-line new.privateConstructor, arguments.count */
        public readonly Optional|string $email = new Optional(),
    ) {}
}
