<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Unsigned;

class LaravelUnsignedTestDto extends SimpleDto
{
    public function __construct(
        #[Unsigned]
        public readonly ?int $quantity = null,
    ) {}
}

describe('Unsigned Attribute - Laravel E2E', function(): void {
    it('passes with zero', function(): void {
        $result = LaravelUnsignedTestDto::validate(['quantity' => 0]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with positive integer', function(): void {
        $result = LaravelUnsignedTestDto::validate(['quantity' => 42]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with negative integer', function(): void {
        $result = LaravelUnsignedTestDto::validate(['quantity' => -5]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('quantity'))->toBeTrue();
    });
});

