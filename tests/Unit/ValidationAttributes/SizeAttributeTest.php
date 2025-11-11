<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Size;

class SizeStringTestDto extends SimpleDto
{
    public function __construct(
        #[Size(5)]
        public readonly ?string $code = null,
    ) {}
}

class SizeNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Size(100)]
        public readonly ?int $value = null,
    ) {}
}

describe('Size Attribute - Plain PHP Validation', function(): void {
    it('passes when string has exact size', function(): void {
        $result = SizeStringTestDto::validate(['code' => 'ABCDE']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is too short', function(): void {
        $result = SizeStringTestDto::validate(['code' => 'ABCD']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('code'))->toBeTrue();
    });

    it('fails when string is too long', function(): void {
        $result = SizeStringTestDto::validate(['code' => 'ABCDEF']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes when number has exact size', function(): void {
        $result = SizeNumericTestDto::validate(['value' => 100]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when number is different', function(): void {
        $result = SizeNumericTestDto::validate(['value' => 99]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('value'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = SizeStringTestDto::validate(['code' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
