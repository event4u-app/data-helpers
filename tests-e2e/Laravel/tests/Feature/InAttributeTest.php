<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\In;

class LaravelInTestDto extends SimpleDto
{
    public function __construct(
        #[In(['admin', 'user', 'guest'])]
        public readonly ?string $role = null,
    ) {}
}

describe('In Attribute - Laravel E2E', function(): void {
    it('passes when in list', function(): void {
        $result = LaravelInTestDto::validate(['role' => 'admin']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with another value in list', function(): void {
        $result = LaravelInTestDto::validate(['role' => 'user']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when not in list', function(): void {
        $result = LaravelInTestDto::validate(['role' => 'superadmin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('role'))->toBeTrue();
    });
});

