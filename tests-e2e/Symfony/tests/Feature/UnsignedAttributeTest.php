<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Unsigned;

class SymfonyUnsignedTestDto extends SimpleDto
{
    public function __construct(
        #[Unsigned]
        public readonly ?int $quantity = null,
    ) {}
}

describe('Unsigned Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with zero', function(): void {
        $result = SymfonyUnsignedTestDto::validate(['quantity' => 0]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with positive integer', function(): void {
        $result = SymfonyUnsignedTestDto::validate(['quantity' => 42]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with negative integer', function(): void {
        $result = SymfonyUnsignedTestDto::validate(['quantity' => -5]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('quantity'))->toBeTrue();
    });
});

