<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\RequiredWith;

class RequiredWithTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $firstName = null,
        #[RequiredWith(['firstName'])]
        public readonly ?string $lastName = null,
    ) {}
}

class RequiredWithMultipleTestDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        #[RequiredWith(['street', 'city'])]
        public readonly ?string $zipCode = null,
    ) {}
}

describe('RequiredWith Attribute - Plain PHP Validation', function(): void {
    it('passes when both fields are provided', function(): void {
        $result = RequiredWithTestDto::validate([
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when neither field is provided', function(): void {
        $result = RequiredWithTestDto::validate([
            'firstName' => null,
            'lastName' => null,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when first field is provided but required field is missing', function(): void {
        $result = RequiredWithTestDto::validate([
            'firstName' => 'John',
            'lastName' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('lastName'))->toBeTrue();
    });

    it('passes when any of multiple fields is present and required field is provided', function(): void {
        $result = RequiredWithMultipleTestDto::validate([
            'street' => '123 Main St',
            'city' => null,
            'zipCode' => '12345',
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when any of multiple fields is present but required field is missing', function(): void {
        $result = RequiredWithMultipleTestDto::validate([
            'street' => '123 Main St',
            'city' => null,
            'zipCode' => null,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('zipCode'))->toBeTrue();
    });
});
