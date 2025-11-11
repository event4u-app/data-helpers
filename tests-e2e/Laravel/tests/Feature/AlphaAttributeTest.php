<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Alpha;

class LaravelAlphaTestDto extends SimpleDto
{
    public function __construct(
        #[Alpha]
        public readonly ?string $name = null,
    ) {}
}

describe('Alpha Attribute - Laravel E2E', function(): void {
    it('passes with only letters', function(): void {
        $result = LaravelAlphaTestDto::validate(['name' => 'JohnDoe']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with numbers', function(): void {
        $result = LaravelAlphaTestDto::validate(['name' => 'John123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });
});

