<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ascii;

class SymfonyAsciiTestDto extends SimpleDto
{
    public function __construct(
        #[Ascii]
        public readonly ?string $text = null,
    ) {}
}

describe('Ascii Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with ASCII characters', function(): void {
        $result = SymfonyAsciiTestDto::validate(['text' => 'Hello World 123!']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with UTF-8 characters', function(): void {
        $result = SymfonyAsciiTestDto::validate(['text' => 'Hëllo Wörld']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('text'))->toBeTrue();
    });
});

