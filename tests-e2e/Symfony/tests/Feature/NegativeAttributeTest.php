<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Negative;

class SymfonyNegativeTestDto extends SimpleDto
{
    public function __construct(
        #[Negative]
        public readonly ?float $debit = null,
    ) {}
}

describe('Negative Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with negative number', function(): void {
        $result = SymfonyNegativeTestDto::validate(['debit' => -42.5]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with positive number', function(): void {
        $result = SymfonyNegativeTestDto::validate(['debit' => 5.5]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('debit'))->toBeTrue();
    });
});

