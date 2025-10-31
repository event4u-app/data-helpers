<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDto;

describe('SimpleDto Null and Missing Keys Tests', function(): void {
    describe('Missing Keys in Template', function(): void {
        it('handles missing top-level key with default value', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {}
            };

            $data = ['other' => 'value'];

            $template = [
                'name' => '{{ missing }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('default');
        });

        it('handles missing nested key with default value', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $email = 'default@example.com',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                ],
            ];

            $template = [
                'email' => '{{ user.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->email)->toBe('default@example.com');
        });

        it('handles missing deeply nested key with default value', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $city = 'Unknown',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                ],
            ];

            $template = [
                'city' => '{{ user.address.city }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->city)->toBe('Unknown');
        });

        it('handles partially missing nested path', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $street = 'Unknown Street',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                    'address' => null,
                ],
            ];

            $template = [
                'street' => '{{ user.address.street }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->street)->toBe('Unknown Street');
        });
    });

    describe('Null Values in Data', function(): void {
        it('handles null value in flat property', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly ?string $name = null,
                ) {}
            };

            $data = ['name' => null];

            $result = $dto::from($data);

            expect($result->name)->toBeNull();
        });

        it('handles null value in nested property with template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly ?string $email = null,
                ) {}
            };

            $data = [
                'user' => [
                    'email' => null,
                ],
            ];

            $template = [
                'email' => '{{ user.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->email)->toBeNull();
        });

        it('handles null value with filter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly ?string $name = null,
                ) {}
            };

            $data = ['name' => null];

            $filters = [
                'name' => new TrimStrings(),
            ];

            $result = $dto::from($data, null, $filters);

            expect($result->name)->toBeNull();
        });

        it('handles null value with pipeline', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly ?string $name = null,
                ) {}
            };

            $data = ['name' => null];

            $pipeline = [new TrimStrings()];

            $result = $dto::from($data, null, null, $pipeline);

            expect($result->name)->toBeNull();
        });

        it('handles mix of null and non-null values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly ?string $name = null,
                    public readonly ?string $email = null,
                    public readonly ?int $age = null,
                ) {}
            };

            $data = [
                'name' => 'John',
                'email' => null,
                'age' => 30,
            ];

            $result = $dto::from($data);

            expect($result->name)->toBe('John')
                ->and($result->email)->toBeNull()
                ->and($result->age)->toBe(30);
        });
    });

    describe('Empty Strings and Values', function(): void {
        it('handles empty string in template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => '',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('');
        });

        it('handles empty string with trim filter', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {}
            };

            $data = ['name' => '   '];

            $filters = [
                'name' => new TrimStrings(),
            ];

            $result = $dto::from($data, null, $filters);

            expect($result->name)->toBe('');
        });

        it('handles empty array in data', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {}
            };

            $data = [];

            $result = $dto::from($data);

            expect($result->name)->toBe('default');
        });

        it('handles empty nested object', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {}
            };

            $data = [
                'user' => [],
            ];

            $template = [
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('default');
        });
    });

    describe('Mixed Scenarios', function(): void {
        it('handles mix of present, missing, and null values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default_name',
                    public readonly ?string $email = null,
                    public readonly ?int $age = 0,
                    public readonly string $city = 'default_city',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                    'email' => null,
                    // age is missing
                    'address' => [
                        // city is missing
                    ],
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
                'email' => '{{ user.email }}',
                'age' => '{{ user.age }}',
                'city' => '{{ user.address.city }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John')
                ->and($result->email)->toBeNull()
                ->and($result->age)->toBe(0)
                ->and($result->city)->toBe('default_city');
        });

        it('handles missing keys with filters and pipeline', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = 'default',
                ) {}
            };

            $data = ['other' => 'value'];

            $template = [
                'name' => '{{ missing }}',
            ];

            $filters = [
                'name' => new TrimStrings(),
            ];

            $pipeline = [new LowercaseStrings()];

            $result = $dto::from($data, $template, $filters, $pipeline);

            expect($result->name)->toBe('default');
        });
    });
});
