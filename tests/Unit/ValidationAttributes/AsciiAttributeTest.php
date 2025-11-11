<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ascii;

class AsciiTestDto extends SimpleDto
{
    public function __construct(
        #[Ascii]
        public readonly ?string $text = null,
    ) {}
}

describe('Ascii Attribute - Plain PHP Validation', function(): void {
    it('passes with ASCII characters', function(): void {
        $result = AsciiTestDto::validate(['text' => 'Hello World 123!']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with UTF-8 characters', function(): void {
        $result = AsciiTestDto::validate(['text' => 'Hëllo Wörld']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('text'))->toBeTrue();
    });

    it('fails with emoji', function(): void {
        $result = AsciiTestDto::validate(['text' => 'Hello 👋']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = AsciiTestDto::validate(['text' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
