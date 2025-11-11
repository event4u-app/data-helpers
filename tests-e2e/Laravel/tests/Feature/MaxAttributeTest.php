<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Max;

class LaravelMaxTestDto extends SimpleDto
{
    public function __construct(
        #[Max(10)]
        public readonly ?string $name = null,
    ) {}
}

describe('Max Attribute - Laravel E2E', function(): void {
    it('passes when within maximum', function(): void {
        $result = LaravelMaxTestDto::validate(['name' => 'John']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when equals maximum', function(): void {
        $result = LaravelMaxTestDto::validate(['name' => str_repeat('a', 10)]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when exceeds maximum', function(): void {
        $result = LaravelMaxTestDto::validate(['name' => str_repeat('a', 11)]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });
});

