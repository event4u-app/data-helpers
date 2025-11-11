<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NotIn;

class LaravelNotInTestDto extends SimpleDto
{
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public readonly ?string $username = null,
    ) {}
}

describe('NotIn Attribute - Laravel E2E', function(): void {
    it('passes when not in forbidden list', function(): void {
        $result = LaravelNotInTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when in forbidden list', function(): void {
        $result = LaravelNotInTestDto::validate(['username' => 'admin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails with another forbidden value', function(): void {
        $result = LaravelNotInTestDto::validate(['username' => 'root']);
        expect($result->isValid())->toBeFalse();
    });
});

