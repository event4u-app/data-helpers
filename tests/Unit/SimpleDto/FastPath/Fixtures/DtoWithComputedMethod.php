<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;

/**
 * DTO with #[Computed] attribute on method.
 * Should NOT be eligible for FastPath.
 */
class DtoWithComputedMethod extends SimpleDto
{
    public function __construct(
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}

