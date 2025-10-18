<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\NotIn;
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;
use event4u\DataHelpers\SimpleDTO\Attributes\Url;
use event4u\DataHelpers\SimpleDTO\Attributes\Uuid;
use event4u\DataHelpers\SimpleDTO\Attributes\Confirmed;
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

describe('Laravel Validation Integration', function () {
    describe('Rule Generation for Laravel', function () {
        it('generates Laravel-compatible rule for Exists attribute', function () {
            $attribute = new Exists('users', 'id');
            expect($attribute->rule())->toBe('exists:users,id');
        });

        it('generates Laravel-compatible rule for Exists with connection', function () {
            $attribute = new Exists('users', 'id', 'mysql');
            expect($attribute->rule())->toBe('exists:mysql.users,id');
        });

        it('generates Laravel-compatible rule for Unique attribute', function () {
            $attribute = new Unique('users', 'email');
            expect($attribute->rule())->toBe('unique:users,email');
        });

        it('generates Laravel-compatible rule for Unique with ignore', function () {
            $attribute = new Unique('users', 'email', 123);
            expect($attribute->rule())->toBe('unique:users,email,123,id');
        });

        it('generates Laravel-compatible rule for Unique with custom id column', function () {
            $attribute = new Unique('users', 'email', 123, 'user_id');
            expect($attribute->rule())->toBe('unique:users,email,123,user_id');
        });

        it('generates Laravel-compatible rule for In attribute', function () {
            $attribute = new In(['admin', 'user', 'guest']);
            expect($attribute->rule())->toBe('in:admin,user,guest');
        });

        it('generates Laravel-compatible rule for NotIn attribute', function () {
            $attribute = new NotIn(['banned', 'deleted']);
            expect($attribute->rule())->toBe('not_in:banned,deleted');
        });

        it('generates Laravel-compatible rule for Between attribute', function () {
            $attribute = new Between(18, 65);
            expect($attribute->rule())->toBe('between:18,65');
        });

        it('generates Laravel-compatible rule for Confirmed attribute', function () {
            $attribute = new Confirmed();
            expect($attribute->rule())->toBe('confirmed');
        });

        it('generates Laravel-compatible rule for File attribute', function () {
            $attribute = new File();
            expect($attribute->rule())->toBe('file');
        });

        it('generates Laravel-compatible rules for File with max size', function () {
            $attribute = new File(maxSize: 10240);
            expect($attribute->rule())->toBe(['file', 'max:10240']);
        });

        it('generates Laravel-compatible rules for File with min and max size', function () {
            $attribute = new File(maxSize: 10240, minSize: 1024);
            expect($attribute->rule())->toBe(['file', 'max:10240', 'min:1024']);
        });

        it('generates Laravel-compatible rules for Image attribute', function () {
            $attribute = new Image();
            expect($attribute->rule())->toBe(['image']);
        });

        it('generates Laravel-compatible rules for Image with mimes', function () {
            $attribute = new Image(mimes: ['jpg', 'png', 'gif']);
            expect($attribute->rule())->toBe(['image', 'mimes:jpg,png,gif']);
        });

        it('generates Laravel-compatible rules for Image with max size', function () {
            $attribute = new Image(maxSize: 2048);
            expect($attribute->rule())->toBe(['image', 'max:2048']);
        });

        it('generates Laravel-compatible rules for Image with dimensions', function () {
            $attribute = new Image(minWidth: 100, maxWidth: 1000, minHeight: 100, maxHeight: 1000);
            $rules = $attribute->rule();
            expect($rules)->toBeArray();
            expect($rules[0])->toBe('image');
            expect($rules[1])->toContain('dimensions:');
            expect($rules[1])->toContain('min_width=100');
            expect($rules[1])->toContain('max_width=1000');
            expect($rules[1])->toContain('min_height=100');
            expect($rules[1])->toContain('max_height=1000');
        });

        it('generates Laravel-compatible rule for Mimes attribute', function () {
            $attribute = new Mimes(['pdf', 'doc', 'docx']);
            expect($attribute->rule())->toBe('mimes:pdf,doc,docx');
        });

        it('generates Laravel-compatible rule for MimeTypes attribute', function () {
            $attribute = new MimeTypes(['application/pdf', 'application/msword']);
            expect($attribute->rule())->toBe('mimetypes:application/pdf,application/msword');
        });

        it('generates Laravel-compatible rule for Size attribute', function () {
            $attribute = new Size(10);
            expect($attribute->rule())->toBe('size:10');
        });

        it('generates Laravel-compatible rule for Same attribute', function () {
            $attribute = new Same('password');
            expect($attribute->rule())->toBe('same:password');
        });

        it('generates Laravel-compatible rule for Different attribute', function () {
            $attribute = new Different('email');
            expect($attribute->rule())->toBe('different:email');
        });

        it('generates Laravel-compatible rule for StartsWith attribute', function () {
            $attribute = new StartsWith(['http://', 'https://']);
            expect($attribute->rule())->toBe('starts_with:http://,https://');
        });

        it('generates Laravel-compatible rule for EndsWith attribute', function () {
            $attribute = new EndsWith(['.com', '.org', '.net']);
            expect($attribute->rule())->toBe('ends_with:.com,.org,.net');
        });

        it('generates Laravel-compatible rule for Ip attribute', function () {
            $attribute = new Ip();
            expect($attribute->rule())->toBe('ip');
        });

        it('generates Laravel-compatible rule for Ip with IPv4', function () {
            $attribute = new Ip(version: 'ipv4');
            expect($attribute->rule())->toBe('ipv4');
        });

        it('generates Laravel-compatible rule for Ip with IPv6', function () {
            $attribute = new Ip(version: 'ipv6');
            expect($attribute->rule())->toBe('ipv6');
        });

        it('generates Laravel-compatible rule for Json attribute', function () {
            $attribute = new Json();
            expect($attribute->rule())->toBe('json');
        });

        it('generates Laravel-compatible rule for Email attribute', function () {
            $attribute = new Email();
            expect($attribute->rule())->toBe('email');
        });

        it('generates Laravel-compatible rule for Url attribute', function () {
            $attribute = new Url();
            expect($attribute->rule())->toBe('url');
        });

        it('generates Laravel-compatible rule for Uuid attribute', function () {
            $attribute = new Uuid();
            expect($attribute->rule())->toBe('uuid');
        });

        it('generates Laravel-compatible rule for Min attribute', function () {
            $attribute = new Min(3);
            expect($attribute->rule())->toBe('min:3');
        });

        it('generates Laravel-compatible rule for Max attribute', function () {
            $attribute = new Max(50);
            expect($attribute->rule())->toBe('max:50');
        });

        it('generates Laravel-compatible rule for Regex attribute', function () {
            $attribute = new Regex('/^[A-Z]{2}[0-9]{4}$/');
            expect($attribute->rule())->toBe('regex:/^[A-Z]{2}[0-9]{4}$/');
        });
    });

    describe('DTO Rule Collection', function () {
        it('collects all rules from DTO attributes', function () {
            $dtoClass = new class('test@example.com', 'John', 25) extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,

                    #[Required]
                    #[Min(3)]
                    #[Max(50)]
                    public readonly string $name,

                    #[Between(18, 120)]
                    public readonly int $age,
                ) {}
            };

            $rules = $dtoClass::getAllRules();

            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');

            expect($rules)->toHaveKey('name');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:3');
            expect($rules['name'])->toContain('max:50');

            expect($rules)->toHaveKey('age');
            expect($rules['age'])->toContain('between:18,120');
        });

        it('merges attribute rules with custom rules', function () {
            $dtoClass = new class('test@example.com') extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,
                ) {}

                protected function rules(): array
                {
                    return [
                        'email' => 'unique:users,email',
                    ];
                }
            };

            $rules = $dtoClass::getAllRules();

            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['email'])->toContain('unique:users,email');
        });

        it('collects complex validation rules', function () {
            $dtoClass = new class('https://example.com', '192.168.1.1', '{}') extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[StartsWith(['http://', 'https://'])]
                    #[EndsWith(['.com', '.org'])]
                    public readonly string $website,

                    #[Required]
                    #[Ip]
                    public readonly string $ipAddress,

                    #[Required]
                    #[Json]
                    public readonly string $settings,
                ) {}
            };

            $rules = $dtoClass::getAllRules();

            expect($rules)->toHaveKey('website');
            expect($rules['website'])->toContain('required');
            expect($rules['website'])->toContain('starts_with:http://,https://');
            expect($rules['website'])->toContain('ends_with:.com,.org');

            expect($rules)->toHaveKey('ipAddress');
            expect($rules['ipAddress'])->toContain('required');
            expect($rules['ipAddress'])->toContain('ip');

            expect($rules)->toHaveKey('settings');
            expect($rules['settings'])->toContain('required');
            expect($rules['settings'])->toContain('json');
        });
    });
});

