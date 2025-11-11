<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;

class UuidTestDto extends SimpleDto
{
    public function __construct(
        #[Uuid]
        public readonly ?string $id = null,
    ) {}
}

describe('Uuid Attribute - Plain PHP Validation', function(): void {
    it('passes with valid UUID v4', function(): void {
        $result = UuidTestDto::validate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with another valid UUID', function(): void {
        $result = UuidTestDto::validate(['id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid UUID format', function(): void {
        $result = UuidTestDto::validate(['id' => 'not-a-uuid']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('id'))->toBeTrue();
    });

    it('fails with incomplete UUID', function(): void {
        $result = UuidTestDto::validate(['id' => '550e8400-e29b-41d4']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = UuidTestDto::validate(['id' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
