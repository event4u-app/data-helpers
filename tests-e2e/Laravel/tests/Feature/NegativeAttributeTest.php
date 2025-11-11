<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Negative;

class LaravelNegativeTestDto extends SimpleDto
{
    public function __construct(
        #[Negative]
        public readonly ?float $debit = null,
    ) {}
}

describe('Negative Attribute - Laravel E2E', function(): void {
    it('passes with negative number', function(): void {
        $result = LaravelNegativeTestDto::validate(['debit' => -42.5]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with positive number', function(): void {
        $result = LaravelNegativeTestDto::validate(['debit' => 5.5]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('debit'))->toBeTrue();
    });
});

