<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Negative;

class NegativeTestDto extends SimpleDto
{
    public function __construct(
        #[Negative]
        public readonly ?float $debit = null,
    ) {}
}

describe('Negative Attribute - Plain PHP Validation', function(): void {
    it('passes with negative number', function(): void {
        $result = NegativeTestDto::validate(['debit' => -42.5]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with zero', function(): void {
        $result = NegativeTestDto::validate(['debit' => 0]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('debit'))->toBeTrue();
    });

    it('fails with positive number', function(): void {
        $result = NegativeTestDto::validate(['debit' => 5.5]);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = NegativeTestDto::validate(['debit' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
