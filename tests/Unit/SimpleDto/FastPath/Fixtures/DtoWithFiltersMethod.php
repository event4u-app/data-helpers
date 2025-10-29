<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with custom filters() method.
 * Should NOT be eligible for FastPath.
 */
class DtoWithFiltersMethod extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}

    /** @return array<string, string> */
    protected function filters(): array
    {
        return [
            'name' => 'trim|lowercase',
        ];
    }
}
