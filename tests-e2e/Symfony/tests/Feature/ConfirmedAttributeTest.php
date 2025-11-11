<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Confirmed;

class SymfonyConfirmedTestDto extends SimpleDto
{
    public function __construct(
        #[Confirmed]
        public readonly ?string $password = null,
        public readonly ?string $password_confirmation = null,
    ) {}
}

describe('Confirmed Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when password and confirmation match', function(): void {
        $result = SymfonyConfirmedTestDto::validate(['password' => 'secret123', 'password_confirmation' => 'secret123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when password and confirmation do not match', function(): void {
        $result = SymfonyConfirmedTestDto::validate(['password' => 'secret123', 'password_confirmation' => 'different']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('password'))->toBeTrue();
    });

    it('fails when confirmation is missing', function(): void {
        $result = SymfonyConfirmedTestDto::validate(['password' => 'secret123', 'password_confirmation' => null]);
        expect($result->isValid())->toBeFalse();
    });
});

