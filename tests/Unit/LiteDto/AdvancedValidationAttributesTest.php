<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Attributes\Validation\In;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Ip;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Json;
use event4u\DataHelpers\LiteDto\Attributes\Validation\NotIn;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs
class AdvancedValidationTestInDto extends LiteDto
{
    public function __construct(
        #[In(['admin', 'user', 'guest'])]
        public readonly string $role,
    ) {}
}

class AdvancedValidationTestNotInDto extends LiteDto
{
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public readonly string $username,
    ) {}
}

class AdvancedValidationTestIpDto extends LiteDto
{
    public function __construct(
        #[Ip]
        public readonly string $ipAddress,
    ) {}
}

class AdvancedValidationTestIpv4Dto extends LiteDto
{
    public function __construct(
        #[Ip(version: 'ipv4')]
        public readonly string $ipAddress,
    ) {}
}

class AdvancedValidationTestIpv6Dto extends LiteDto
{
    public function __construct(
        #[Ip(version: 'ipv6')]
        public readonly string $ipAddress,
    ) {}
}

class AdvancedValidationTestJsonDto extends LiteDto
{
    public function __construct(
        #[Json]
        public readonly string $settings,
    ) {}
}

class AdvancedValidationTestMultipleDto extends LiteDto
{
    public function __construct(
        #[In(['active', 'inactive', 'pending'])]
        public readonly string $status,
        #[NotIn([0, -1])]
        public readonly int $userId,
    ) {}
}

describe('LiteDto Advanced Validation Attributes', function(): void {
    describe('In Attribute', function(): void {
        it('validates value in allowed list', function(): void {
            $dto = AdvancedValidationTestInDto::from(['role' => 'admin']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates another value in allowed list', function(): void {
            $dto = AdvancedValidationTestInDto::from(['role' => 'user']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for value not in allowed list', function(): void {
            $dto = AdvancedValidationTestInDto::from(['role' => 'superadmin']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('role'))->toBeTrue();
        });
    });

    describe('NotIn Attribute', function(): void {
        it('validates value not in forbidden list', function(): void {
            $dto = AdvancedValidationTestNotInDto::from(['username' => 'john']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for value in forbidden list', function(): void {
            $dto = AdvancedValidationTestNotInDto::from(['username' => 'admin']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('username'))->toBeTrue();
        });

        it('fails for another value in forbidden list', function(): void {
            $dto = AdvancedValidationTestNotInDto::from(['username' => 'root']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('username'))->toBeTrue();
        });
    });

    describe('Ip Attribute', function(): void {
        it('validates IPv4 address', function(): void {
            $dto = AdvancedValidationTestIpDto::from(['ipAddress' => '192.168.1.1']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates IPv6 address', function(): void {
            $dto = AdvancedValidationTestIpDto::from(['ipAddress' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates shortened IPv6 address', function(): void {
            $dto = AdvancedValidationTestIpDto::from(['ipAddress' => '::1']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid IP address', function(): void {
            $dto = AdvancedValidationTestIpDto::from(['ipAddress' => 'not-an-ip']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('ipAddress'))->toBeTrue();
        });

        it('fails for invalid IPv4 format', function(): void {
            $dto = AdvancedValidationTestIpDto::from(['ipAddress' => '999.999.999.999']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
        });
    });

    describe('Ip Attribute with IPv4 version', function(): void {
        it('validates IPv4 address', function(): void {
            $dto = AdvancedValidationTestIpv4Dto::from(['ipAddress' => '192.168.1.1']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for IPv6 address when IPv4 is required', function(): void {
            $dto = AdvancedValidationTestIpv4Dto::from(['ipAddress' => '::1']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('ipAddress'))->toBeTrue();
        });
    });

    describe('Ip Attribute with IPv6 version', function(): void {
        it('validates IPv6 address', function(): void {
            $dto = AdvancedValidationTestIpv6Dto::from(['ipAddress' => '2001:0db8:85a3::8a2e:0370:7334']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for IPv4 address when IPv6 is required', function(): void {
            $dto = AdvancedValidationTestIpv6Dto::from(['ipAddress' => '192.168.1.1']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('ipAddress'))->toBeTrue();
        });
    });

    describe('Json Attribute', function(): void {
        it('validates valid JSON object', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => '{"key":"value"}']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid JSON array', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => '["item1","item2"]']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid JSON string', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => '"string"']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid JSON number', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => '123']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid JSON boolean', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => 'true']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('validates valid JSON null', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => 'null']);
            $result = $dto->validateInstance();
            expect($result->isValid())->toBeTrue();
        });

        it('fails for invalid JSON', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => '{invalid json}']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('settings'))->toBeTrue();
        });

        it('fails for incomplete JSON', function(): void {
            $dto = AdvancedValidationTestJsonDto::from(['settings' => '{"key":']);
            $result = $dto->validateInstance();
            expect($result->isFailed())->toBeTrue();
        });
    });

    describe('Multiple Advanced Attributes', function(): void {
        it('validates when all conditions are met', function(): void {
            $result = AdvancedValidationTestMultipleDto::validate([
                'status' => 'active',
                'userId' => 123,
            ]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when In condition is not met', function(): void {
            $result = AdvancedValidationTestMultipleDto::validate([
                'status' => 'deleted',
                'userId' => 123,
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('status'))->toBeTrue();
        });

        it('fails when NotIn condition is not met', function(): void {
            $result = AdvancedValidationTestMultipleDto::validate([
                'status' => 'active',
                'userId' => 0,
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('userId'))->toBeTrue();
        });

        it('fails when both conditions are not met', function(): void {
            $result = AdvancedValidationTestMultipleDto::validate([
                'status' => 'deleted',
                'userId' => -1,
            ]);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('status'))->toBeTrue();
            expect($result->hasError('userId'))->toBeTrue();
        });
    });

    describe('validate() static method', function(): void {
        it('validates data before creating DTO', function(): void {
            $result = AdvancedValidationTestInDto::validate(['role' => 'admin']);
            expect($result->isValid())->toBeTrue();
        });

        it('returns errors for invalid data', function(): void {
            $result = AdvancedValidationTestInDto::validate(['role' => 'invalid']);
            expect($result->isFailed())->toBeTrue();
            expect($result->hasError('role'))->toBeTrue();
        });
    });

    describe('validateAndCreate() static method', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = AdvancedValidationTestIpDto::validateAndCreate(['ipAddress' => '192.168.1.1']);
            expect($dto->ipAddress)->toBe('192.168.1.1');
        });

        it('throws exception when validation fails', function(): void {
            expect(
                fn(): \AdvancedValidationTestIpDto => AdvancedValidationTestIpDto::validateAndCreate([
                    'ipAddress' => 'invalid']
                )
            )
                ->toThrow(ValidationException::class);
        });
    });
});
