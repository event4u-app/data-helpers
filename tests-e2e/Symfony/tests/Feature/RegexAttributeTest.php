<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;

class SymfonyRegexTestDto extends SimpleDto
{
    public function __construct(
        #[Regex('/^[A-Z]{2}\d{4}$/')]
        public readonly ?string $code = null,
    ) {}
}

describe('Regex Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when value matches pattern', function(): void {
        $result = SymfonyRegexTestDto::validate(['code' => 'AB1234']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when value does not match pattern', function(): void {
        $result = SymfonyRegexTestDto::validate(['code' => 'abc123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('code'))->toBeTrue();
    });
});

