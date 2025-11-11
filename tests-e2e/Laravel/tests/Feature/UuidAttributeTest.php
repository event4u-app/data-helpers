<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;

class LaravelUuidTestDto extends SimpleDto
{
    public function __construct(
        #[Uuid]
        public readonly ?string $id = null,
    ) {}
}

describe('Uuid Attribute - Laravel E2E', function(): void {
    it('passes with valid UUID', function(): void {
        $result = LaravelUuidTestDto::validate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid UUID', function(): void {
        $result = LaravelUuidTestDto::validate(['id' => 'not-a-uuid']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('id'))->toBeTrue();
    });
});

