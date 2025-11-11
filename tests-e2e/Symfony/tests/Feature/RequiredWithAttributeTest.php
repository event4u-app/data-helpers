<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWith;

class SymfonyRequiredWithTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $firstName = null,
        #[RequiredWith(['firstName'])]
        public readonly ?string $lastName = null,
    ) {}
}

describe('RequiredWith Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when both fields are provided', function(): void {
        $result = SymfonyRequiredWithTestDto::validate(['firstName' => 'John', 'lastName' => 'Doe']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when neither field is provided', function(): void {
        $result = SymfonyRequiredWithTestDto::validate(['firstName' => null, 'lastName' => null]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when first field is provided but required field is missing', function(): void {
        $result = SymfonyRequiredWithTestDto::validate(['firstName' => 'John', 'lastName' => null]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('lastName'))->toBeTrue();
    });
});

