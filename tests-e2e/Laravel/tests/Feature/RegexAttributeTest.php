<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;

class LaravelRegexTestDto extends SimpleDto
{
    public function __construct(
        #[Regex('/^[A-Z]{2}\d{4}$/')]
        public readonly ?string $code = null,
    ) {}
}

describe('Regex Attribute - Laravel E2E', function(): void {
    it('passes when value matches pattern', function(): void {
        $result = LaravelRegexTestDto::validate(['code' => 'AB1234']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when value does not match pattern', function(): void {
        $result = LaravelRegexTestDto::validate(['code' => 'abc123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('code'))->toBeTrue();
    });
});

