<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class LaravelEmailTestDto extends SimpleDto
{
    public function __construct(
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

describe('Email Attribute - Laravel E2E', function(): void {
    it('passes with valid email', function(): void {
        $result = LaravelEmailTestDto::validate(['email' => 'test@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with email containing plus', function(): void {
        $result = LaravelEmailTestDto::validate(['email' => 'test+tag@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid email', function(): void {
        $result = LaravelEmailTestDto::validate(['email' => 'invalid-email']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('email'))->toBeTrue();
    });

    it('fails with missing @', function(): void {
        $result = LaravelEmailTestDto::validate(['email' => 'testexample.com']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = LaravelEmailTestDto::validate(['email' => null]);
        expect($result->isValid())->toBeTrue();
    });
});

