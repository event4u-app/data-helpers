<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Positive;

class PositiveTestDto extends SimpleDto
{
    public function __construct(
        #[Positive]
        public readonly ?int $count = null,
    ) {}
}

describe('Positive Attribute - Plain PHP Validation', function(): void {
    it('passes with positive integer', function(): void {
        $result = PositiveTestDto::validate(['count' => 42]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with zero', function(): void {
        $result = PositiveTestDto::validate(['count' => 0]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('count'))->toBeTrue();
    });

    it('fails with negative integer', function(): void {
        $result = PositiveTestDto::validate(['count' => -5]);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = PositiveTestDto::validate(['count' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
