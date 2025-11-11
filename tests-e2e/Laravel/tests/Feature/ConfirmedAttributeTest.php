<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Confirmed;

class LaravelConfirmedTestDto extends SimpleDto
{
    public function __construct(
        #[Confirmed]
        public readonly ?string $password = null,
        public readonly ?string $password_confirmation = null,
    ) {}
}

describe('Confirmed Attribute - Laravel E2E', function(): void {
    it('passes when password and confirmation match', function(): void {
        $result = LaravelConfirmedTestDto::validate(['password' => 'secret123', 'password_confirmation' => 'secret123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when password and confirmation do not match', function(): void {
        $result = LaravelConfirmedTestDto::validate(['password' => 'secret123', 'password_confirmation' => 'different']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when confirmation is missing', function(): void {
        $result = LaravelConfirmedTestDto::validate(['password' => 'secret123', 'password_confirmation' => null]);
        expect($result->isValid())->toBeFalse();
    });
});

