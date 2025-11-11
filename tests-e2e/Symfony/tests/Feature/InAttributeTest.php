<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;

class SymfonyInTestDto extends SimpleDto
{
    public function __construct(
        #[In(['admin', 'user', 'guest'])]
        public readonly ?string $role = null,
    ) {}
}

describe('In Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when in list', function(): void {
        $result = SymfonyInTestDto::validate(['role' => 'admin']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with another value in list', function(): void {
        $result = SymfonyInTestDto::validate(['role' => 'user']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when not in list', function(): void {
        $result = SymfonyInTestDto::validate(['role' => 'superadmin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('role'))->toBeTrue();
    });
});

