<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class EmailTestDto extends SimpleDto
{
    public function __construct(
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

describe('Email Attribute - Plain PHP Validation', function(): void {
    it('passes with valid email', function(): void {
        $result = EmailTestDto::validate(['email' => 'test@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with email containing plus', function(): void {
        $result = EmailTestDto::validate(['email' => 'test+tag@example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with subdomain', function(): void {
        $result = EmailTestDto::validate(['email' => 'test@mail.example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid email', function(): void {
        $result = EmailTestDto::validate(['email' => 'invalid-email']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('email'))->toBeTrue();
    });

    it('fails with missing @', function(): void {
        $result = EmailTestDto::validate(['email' => 'testexample.com']);
        expect($result->isValid())->toBeFalse();
    });

    it('fails with missing domain', function(): void {
        $result = EmailTestDto::validate(['email' => 'test@']);
        expect($result->isValid())->toBeFalse();
    });

    it('fails with missing local part', function(): void {
        $result = EmailTestDto::validate(['email' => '@example.com']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = EmailTestDto::validate(['email' => null]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with empty string', function(): void {
        $result = EmailTestDto::validate(['email' => '']);
        expect($result->isValid())->toBeTrue();
    });
});
