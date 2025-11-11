<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Length;

class LengthMaxTestDto extends SimpleDto
{
    public function __construct(
        #[Length(10)]
        public readonly ?string $name = null,
    ) {}
}

class LengthRangeTestDto extends SimpleDto
{
    public function __construct(
        #[Length(3, 10)]
        public readonly ?string $username = null,
    ) {}
}

describe('Length Attribute - Plain PHP Validation', function(): void {
    it('passes when string is within max length', function(): void {
        $result = LengthMaxTestDto::validate(['name' => 'John']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string equals max length', function(): void {
        $result = LengthMaxTestDto::validate(['name' => str_repeat('a', 10)]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string exceeds max length', function(): void {
        $result = LengthMaxTestDto::validate(['name' => str_repeat('a', 11)]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('passes when string is within range', function(): void {
        $result = LengthRangeTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string equals min length', function(): void {
        $result = LengthRangeTestDto::validate(['username' => 'joe']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string equals max length in range', function(): void {
        $result = LengthRangeTestDto::validate(['username' => str_repeat('a', 10)]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is below min length', function(): void {
        $result = LengthRangeTestDto::validate(['username' => 'jo']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails when string exceeds max length in range', function(): void {
        $result = LengthRangeTestDto::validate(['username' => str_repeat('a', 11)]);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = LengthMaxTestDto::validate(['name' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
