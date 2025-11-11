<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Positive;

class SymfonyPositiveTestDto extends SimpleDto
{
    public function __construct(
        #[Positive]
        public readonly ?int $count = null,
    ) {}
}

describe('Positive Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with positive integer', function(): void {
        $result = SymfonyPositiveTestDto::validate(['count' => 42]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with zero', function(): void {
        $result = SymfonyPositiveTestDto::validate(['count' => 0]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('count'))->toBeTrue();
    });
});

