<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ip;

class SymfonyIpTestDto extends SimpleDto
{
    public function __construct(
        #[Ip]
        public readonly ?string $ipAddress = null,
    ) {}
}

describe('Ip Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with valid IPv4 address', function(): void {
        $result = SymfonyIpTestDto::validate(['ipAddress' => '192.168.1.1']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with valid IPv6 address', function(): void {
        $result = SymfonyIpTestDto::validate(['ipAddress' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid IP address', function(): void {
        $result = SymfonyIpTestDto::validate(['ipAddress' => 'not-an-ip']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('ipAddress'))->toBeTrue();
    });
});

