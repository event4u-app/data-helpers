<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\StartsWith;

class SymfonyStartsWithTestDto extends SimpleDto
{
    public function __construct(
        #[StartsWith(['http://', 'https://'])]
        public readonly ?string $url = null,
    ) {}
}

describe('StartsWith Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when string starts with prefix', function(): void {
        $result = SymfonyStartsWithTestDto::validate(['url' => 'https://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string does not start with prefix', function(): void {
        $result = SymfonyStartsWithTestDto::validate(['url' => 'ftp://example.com']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('url'))->toBeTrue();
    });
});

