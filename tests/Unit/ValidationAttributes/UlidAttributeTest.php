<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ulid;

class UlidTestDto extends SimpleDto
{
    public function __construct(
        #[Ulid]
        public readonly ?string $id = null,
    ) {}
}

describe('Ulid Attribute - Plain PHP Validation', function(): void {
    it('passes with valid ULID', function(): void {
        $result = UlidTestDto::validate(['id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with lowercase ULID', function(): void {
        $result = UlidTestDto::validate(['id' => '01arz3ndektsv4rrffq69g5fav']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid length', function(): void {
        $result = UlidTestDto::validate(['id' => '01ARZ3NDEKTSV4RRFFQ69G5FA']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('id'))->toBeTrue();
    });

    it('fails with invalid characters', function(): void {
        $result = UlidTestDto::validate(['id' => '01ARZ3NDEKTSV4RRFFQ69G5FIL']);
        expect($result->isValid())->toBeFalse();
    });

    it('fails with UUID format', function(): void {
        $result = UlidTestDto::validate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = UlidTestDto::validate(['id' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
