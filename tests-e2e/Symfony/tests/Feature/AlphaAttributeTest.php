<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Alpha;

class SymfonyAlphaTestDto extends SimpleDto
{
    public function __construct(
        #[Alpha]
        public readonly ?string $name = null,
    ) {}
}

describe('Alpha Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with only letters', function(): void {
        $result = SymfonyAlphaTestDto::validate(['name' => 'JohnDoe']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with numbers', function(): void {
        $result = SymfonyAlphaTestDto::validate(['name' => 'John123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });
});

