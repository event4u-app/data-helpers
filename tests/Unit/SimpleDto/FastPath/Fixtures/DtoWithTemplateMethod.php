<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with custom template() method.
 * Should NOT be eligible for FastPath.
 */
class DtoWithTemplateMethod extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}

    protected function template(): array
    {
        return [
            'name' => 'name',
        ];
    }
}

