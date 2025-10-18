<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Size;
use event4u\DataHelpers\SimpleDTO\Attributes\Same;
use event4u\DataHelpers\SimpleDTO\Attributes\Different;
use event4u\DataHelpers\SimpleDTO\Attributes\StartsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\EndsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\Ip;
use event4u\DataHelpers\SimpleDTO\Attributes\Json;
use event4u\DataHelpers\SimpleDTO\Attributes\Exists;
use event4u\DataHelpers\SimpleDTO\Attributes\Unique;
use event4u\DataHelpers\SimpleDTO\Attributes\File;
use event4u\DataHelpers\SimpleDTO\Attributes\Image;
use event4u\DataHelpers\SimpleDTO\Attributes\Mimes;
use event4u\DataHelpers\SimpleDTO\Attributes\MimeTypes;
use event4u\DataHelpers\Exceptions\ValidationException;

// Test DTOs
class SizeTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Size(10)]
        public readonly string $phoneNumber,
    ) {}
}

class SameTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $password,

        #[Required]
        #[Same('password')]
        public readonly string $passwordConfirmation,
    ) {}
}

class DifferentTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $email,

        #[Required]
        #[Different('email')]
        public readonly string $alternativeEmail,
    ) {}
}

class StartsWithTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[StartsWith(['http://', 'https://'])]
        public readonly string $url,
    ) {}
}

class EndsWithTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[EndsWith(['.com', '.org', '.net'])]
        public readonly string $domain,
    ) {}
}

class IpTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Ip]
        public readonly string $ipAddress,
    ) {}
}

class JsonTestDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Json]
        public readonly string $settings,
    ) {}
}

describe('Advanced Validation Attributes', function () {
    describe('Size Attribute', function () {
        it('validates exact string length', function () {
            $dto = SizeTestDTO::validateAndCreate(['phoneNumber' => '1234567890']);
            expect($dto->phoneNumber)->toBe('1234567890');
        });

        it('fails when string length is too short', function () {
            SizeTestDTO::validateAndCreate(['phoneNumber' => '123']);
        })->throws(ValidationException::class);

        it('fails when string length is too long', function () {
            SizeTestDTO::validateAndCreate(['phoneNumber' => '12345678901']);
        })->throws(ValidationException::class);
    });

    describe('Same Attribute', function () {
        it('validates matching fields', function () {
            $dto = SameTestDTO::validateAndCreate([
                'password' => 'secret123',
                'passwordConfirmation' => 'secret123',
            ]);
            expect($dto->password)->toBe('secret123');
        });

        it('fails when fields do not match', function () {
            SameTestDTO::validateAndCreate([
                'password' => 'secret123',
                'passwordConfirmation' => 'different',
            ]);
        })->throws(ValidationException::class);
    });

    describe('Different Attribute', function () {
        it('validates different fields', function () {
            $dto = DifferentTestDTO::validateAndCreate([
                'email' => 'john@example.com',
                'alternativeEmail' => 'jane@example.com',
            ]);
            expect($dto->email)->toBe('john@example.com');
            expect($dto->alternativeEmail)->toBe('jane@example.com');
        });

        it('fails when fields are the same', function () {
            DifferentTestDTO::validateAndCreate([
                'email' => 'john@example.com',
                'alternativeEmail' => 'john@example.com',
            ]);
        })->throws(ValidationException::class);
    });

    describe('StartsWith Attribute', function () {
        it('validates string starting with http://', function () {
            $dto = StartsWithTestDTO::validateAndCreate(['url' => 'http://example.com']);
            expect($dto->url)->toBe('http://example.com');
        });

        it('validates string starting with https://', function () {
            $dto = StartsWithTestDTO::validateAndCreate(['url' => 'https://example.com']);
            expect($dto->url)->toBe('https://example.com');
        });

        it('fails when string does not start with allowed prefix', function () {
            StartsWithTestDTO::validateAndCreate(['url' => 'ftp://example.com']);
        })->throws(ValidationException::class);
    });

    describe('EndsWith Attribute', function () {
        it('validates string ending with .com', function () {
            $dto = EndsWithTestDTO::validateAndCreate(['domain' => 'example.com']);
            expect($dto->domain)->toBe('example.com');
        });

        it('validates string ending with .org', function () {
            $dto = EndsWithTestDTO::validateAndCreate(['domain' => 'example.org']);
            expect($dto->domain)->toBe('example.org');
        });

        it('validates string ending with .net', function () {
            $dto = EndsWithTestDTO::validateAndCreate(['domain' => 'example.net']);
            expect($dto->domain)->toBe('example.net');
        });

        it('fails when string does not end with allowed suffix', function () {
            EndsWithTestDTO::validateAndCreate(['domain' => 'example.de']);
        })->throws(ValidationException::class);
    });

    describe('Ip Attribute', function () {
        it('validates IPv4 address', function () {
            $dto = IpTestDTO::validateAndCreate(['ipAddress' => '192.168.1.1']);
            expect($dto->ipAddress)->toBe('192.168.1.1');
        });

        it('validates IPv6 address', function () {
            $dto = IpTestDTO::validateAndCreate(['ipAddress' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
            expect($dto->ipAddress)->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        });

        it('fails when IP address is invalid', function () {
            IpTestDTO::validateAndCreate(['ipAddress' => '999.999.999.999']);
        })->throws(ValidationException::class);
    });

    describe('Json Attribute', function () {
        it('validates valid JSON string', function () {
            $dto = JsonTestDTO::validateAndCreate(['settings' => '{"key": "value"}']);
            expect($dto->settings)->toBe('{"key": "value"}');
        });

        it('validates empty JSON object', function () {
            $dto = JsonTestDTO::validateAndCreate(['settings' => '{}']);
            expect($dto->settings)->toBe('{}');
        });

        it('validates JSON array', function () {
            $dto = JsonTestDTO::validateAndCreate(['settings' => '[1, 2, 3]']);
            expect($dto->settings)->toBe('[1, 2, 3]');
        });

        it('fails when JSON is invalid', function () {
            JsonTestDTO::validateAndCreate(['settings' => 'not-json']);
        })->throws(ValidationException::class);
    });

    describe('Attribute Rule Generation', function () {
        it('generates correct rule for Size attribute', function () {
            $attribute = new Size(10);
            expect($attribute->rule())->toBe('size:10');
        });

        it('generates correct rule for Same attribute', function () {
            $attribute = new Same('password');
            expect($attribute->rule())->toBe('same:password');
        });

        it('generates correct rule for Different attribute', function () {
            $attribute = new Different('email');
            expect($attribute->rule())->toBe('different:email');
        });

        it('generates correct rule for StartsWith attribute', function () {
            $attribute = new StartsWith(['http://', 'https://']);
            expect($attribute->rule())->toBe('starts_with:http://,https://');
        });

        it('generates correct rule for EndsWith attribute', function () {
            $attribute = new EndsWith(['.com', '.org']);
            expect($attribute->rule())->toBe('ends_with:.com,.org');
        });

        it('generates correct rule for Ip attribute', function () {
            $attribute = new Ip();
            expect($attribute->rule())->toBe('ip');
        });

        it('generates correct rule for Ip attribute with IPv4', function () {
            $attribute = new Ip(version: 'ipv4');
            expect($attribute->rule())->toBe('ipv4');
        });

        it('generates correct rule for Json attribute', function () {
            $attribute = new Json();
            expect($attribute->rule())->toBe('json');
        });

        it('generates correct rule for Exists attribute', function () {
            $attribute = new Exists('users', 'id');
            expect($attribute->rule())->toBe('exists:users,id');
        });

        it('generates correct rule for Unique attribute', function () {
            $attribute = new Unique('users', 'email');
            expect($attribute->rule())->toBe('unique:users,email');
        });

        it('generates correct rule for File attribute', function () {
            $attribute = new File();
            expect($attribute->rule())->toBe('file');
        });

        it('generates correct rule for File attribute with max size', function () {
            $attribute = new File(maxSize: 10240);
            expect($attribute->rule())->toBe(['file', 'max:10240']);
        });

        it('generates correct rule for Image attribute', function () {
            $attribute = new Image();
            expect($attribute->rule())->toBe(['image']);
        });

        it('generates correct rule for Image attribute with mimes', function () {
            $attribute = new Image(mimes: ['jpg', 'png']);
            expect($attribute->rule())->toBe(['image', 'mimes:jpg,png']);
        });

        it('generates correct rule for Mimes attribute', function () {
            $attribute = new Mimes(['pdf', 'doc']);
            expect($attribute->rule())->toBe('mimes:pdf,doc');
        });

        it('generates correct rule for MimeTypes attribute', function () {
            $attribute = new MimeTypes(['application/pdf', 'application/msword']);
            expect($attribute->rule())->toBe('mimetypes:application/pdf,application/msword');
        });
    });
});

