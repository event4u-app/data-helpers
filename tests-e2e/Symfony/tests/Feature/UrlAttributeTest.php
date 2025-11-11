<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Url;

class SymfonyUrlTestDto extends SimpleDto
{
    public function __construct(
        #[Url]
        public readonly ?string $website = null,
    ) {}
}

describe('Url Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with valid HTTPS URL', function(): void {
        $result = SymfonyUrlTestDto::validate(['website' => 'https://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with valid HTTP URL', function(): void {
        $result = SymfonyUrlTestDto::validate(['website' => 'http://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid URL', function(): void {
        $result = SymfonyUrlTestDto::validate(['website' => 'invalid-url']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('website'))->toBeTrue();
    });
});

