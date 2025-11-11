<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NotIn;

class SymfonyNotInTestDto extends SimpleDto
{
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public readonly ?string $username = null,
    ) {}
}

describe('NotIn Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when not in forbidden list', function(): void {
        $result = SymfonyNotInTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when in forbidden list', function(): void {
        $result = SymfonyNotInTestDto::validate(['username' => 'admin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails with another forbidden value', function(): void {
        $result = SymfonyNotInTestDto::validate(['username' => 'root']);
        expect($result->isValid())->toBeFalse();
    });
});

