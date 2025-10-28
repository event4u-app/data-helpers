<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with custom rules() method.
 * Should NOT be eligible for FastPath.
 */
class DtoWithRulesMethod extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}

    protected function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }
}
