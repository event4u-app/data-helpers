<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Unsigned;

class UnsignedTestDto extends SimpleDto
{
    public function __construct(
        #[Unsigned]
        public readonly ?int $quantity = null,
    ) {}
}

describe('Unsigned Attribute - Plain PHP Validation', function(): void {
    it('passes with zero', function(): void {
        $result = UnsignedTestDto::validate(['quantity' => 0]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with positive integer', function(): void {
        $result = UnsignedTestDto::validate(['quantity' => 42]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with negative integer', function(): void {
        $result = UnsignedTestDto::validate(['quantity' => -5]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('quantity'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = UnsignedTestDto::validate(['quantity' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
