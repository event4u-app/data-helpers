<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;

class InTestDto extends SimpleDto
{
    public function __construct(
        #[In(['admin', 'user', 'guest'])]
        public readonly ?string $role = null,
    ) {}
}

describe('In Attribute - Plain PHP Validation', function(): void {
    it('passes when value is in list', function(): void {
        $result = InTestDto::validate(['role' => 'admin']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with another value in list', function(): void {
        $result = InTestDto::validate(['role' => 'user']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when value is not in list', function(): void {
        $result = InTestDto::validate(['role' => 'superadmin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('role'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = InTestDto::validate(['role' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
