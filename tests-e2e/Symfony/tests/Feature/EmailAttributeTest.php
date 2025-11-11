<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class SymfonyEmailTestDto extends SimpleDto
{
    public function __construct(
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

describe('Email Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with valid email', function(): void {
        $result = SymfonyEmailTestDto::validate(['email' => 'test@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with email containing plus', function(): void {
        $result = SymfonyEmailTestDto::validate(['email' => 'test+tag@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid email', function(): void {
        $result = SymfonyEmailTestDto::validate(['email' => 'invalid-email']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('email'))->toBeTrue();
    });

    it('fails with missing @', function(): void {
        $result = SymfonyEmailTestDto::validate(['email' => 'testexample.com']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = SymfonyEmailTestDto::validate(['email' => null]);
        expect($result->isValid())->toBeTrue();
    });
});

