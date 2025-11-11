<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConfirmedBy;

class LaravelConfirmedByTestDto extends SimpleDto
{
    public function __construct(
        #[ConfirmedBy('passwordConfirm')]
        public readonly ?string $password = null,
        public readonly ?string $passwordConfirm = null,
    ) {}
}

describe('ConfirmedBy Attribute - Laravel E2E', function(): void {
    it('passes when password and custom confirmation match', function(): void {
        $result = LaravelConfirmedByTestDto::validate(['password' => 'secret123', 'passwordConfirm' => 'secret123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when password and custom confirmation do not match', function(): void {
        $result = LaravelConfirmedByTestDto::validate(['password' => 'secret123', 'passwordConfirm' => 'different']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when custom confirmation is missing', function(): void {
        $result = LaravelConfirmedByTestDto::validate(['password' => 'secret123', 'passwordConfirm' => null]);
        expect($result->isValid())->toBeFalse();
    });
});

