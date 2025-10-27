<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with custom casts() method.
 * Should NOT be eligible for FastPath.
 */
class DtoWithCastsMethod extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $age = null,
    ) {}

    protected function casts(): array
    {
        return [
            'age' => 'int',
        ];
    }
}

