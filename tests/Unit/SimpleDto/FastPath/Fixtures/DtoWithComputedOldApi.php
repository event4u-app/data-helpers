<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with custom computed() method (old API).
 * Should NOT be eligible for FastPath.
 */
class DtoWithComputedOldApi extends SimpleDto
{
    public function __construct(
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
    ) {}

    protected function computed(): array
    {
        return [
            'fullName' => fn() => trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? '')),
        ];
    }
}

