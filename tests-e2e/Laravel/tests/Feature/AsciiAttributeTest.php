<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ascii;

class LaravelAsciiTestDto extends SimpleDto
{
    public function __construct(
        #[Ascii]
        public readonly ?string $text = null,
    ) {}
}

describe('Ascii Attribute - Laravel E2E', function(): void {
    it('passes with ASCII characters', function(): void {
        $result = LaravelAsciiTestDto::validate(['text' => 'Hello World 123!']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with UTF-8 characters', function(): void {
        $result = LaravelAsciiTestDto::validate(['text' => 'Hëllo Wörld']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('text'))->toBeTrue();
    });
});

