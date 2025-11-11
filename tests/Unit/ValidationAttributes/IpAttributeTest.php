<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ip;

class IpTestDto extends SimpleDto
{
    public function __construct(
        #[Ip]
        public readonly ?string $ipAddress = null,
    ) {}
}

describe('Ip Attribute - Plain PHP Validation', function(): void {
    it('passes with valid IPv4 address', function(): void {
        $result = IpTestDto::validate(['ipAddress' => '192.168.1.1']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with another valid IPv4', function(): void {
        $result = IpTestDto::validate(['ipAddress' => '10.0.0.1']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with valid IPv6 address', function(): void {
        $result = IpTestDto::validate(['ipAddress' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid IP address', function(): void {
        $result = IpTestDto::validate(['ipAddress' => '999.999.999.999']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('ipAddress'))->toBeTrue();
    });

    it('fails with non-IP string', function(): void {
        $result = IpTestDto::validate(['ipAddress' => 'not-an-ip']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = IpTestDto::validate(['ipAddress' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
