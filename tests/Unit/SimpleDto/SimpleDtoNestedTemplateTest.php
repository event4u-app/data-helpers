<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDto;

describe('SimpleDto Nested Template Tests', function(): void {
    describe('Simple Nested Templates', function(): void {
        it('handles two-level nested template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'id' => 123,
                    'name' => 'John',
                ],
            ];

            $template = [
                'id' => '{{ user.id }}',
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John');
        });

        it('handles three-level nested template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'data' => [
                    'user' => [
                        'id' => 456,
                        'email' => 'john@example.com',
                    ],
                ],
            ];

            $template = [
                'id' => '{{ data.user.id }}',
                'email' => '{{ data.user.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(456)
                ->and($result->email)->toBe('john@example.com');
        });

        it('handles four-level nested template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $city = '',
                ) {}
            };

            $data = [
                'response' => [
                    'data' => [
                        'user' => [
                            'address' => [
                                'city' => 'Berlin',
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'city' => '{{ response.data.user.address.city }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->city)->toBe('Berlin');
        });

        it('handles five-level nested template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $street = '',
                ) {}
            };

            $data = [
                'api' => [
                    'response' => [
                        'data' => [
                            'user' => [
                                'address' => [
                                    'street' => 'Main Street',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'street' => '{{ api.response.data.user.address.street }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->street)->toBe('Main Street');
        });
    });

    describe('Mixed Nested and Flat Templates', function(): void {
        it('handles mix of nested and flat properties', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'id' => 123,
                'user' => [
                    'name' => 'John',
                    'contact' => [
                        'email' => 'john@example.com',
                    ],
                ],
            ];

            $template = [
                'id' => '{{ id }}',
                'name' => '{{ user.name }}',
                'email' => '{{ user.contact.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John')
                ->and($result->email)->toBe('john@example.com');
        });

        it('handles multiple properties from same nested object', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $firstName = '',
                    public readonly string $lastName = '',
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'user' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@example.com',
                ],
            ];

            $template = [
                'firstName' => '{{ user.firstName }}',
                'lastName' => '{{ user.lastName }}',
                'email' => '{{ user.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->firstName)->toBe('John')
                ->and($result->lastName)->toBe('Doe')
                ->and($result->email)->toBe('john@example.com');
        });

        it('handles properties from different nested objects', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly string $city = '',
                    public readonly string $company = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                ],
                'address' => [
                    'city' => 'Berlin',
                ],
                'work' => [
                    'company' => 'Acme Inc',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
                'city' => '{{ address.city }}',
                'company' => '{{ work.company }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John')
                ->and($result->city)->toBe('Berlin')
                ->and($result->company)->toBe('Acme Inc');
        });
    });

    describe('Nested Templates with Filters', function(): void {
        it('applies filters to nested template values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => '  JOHN  ',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
            ];

            $filters = [
                'name' => new TrimStrings(),
            ];

            $result = $dto::from($data, $template, $filters);

            expect($result->name)->toBe('JOHN');
        });

        it('applies multiple filters to nested template values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'profile' => [
                        'name' => '  JOHN  ',
                    ],
                ],
            ];

            $template = [
                'name' => '{{ user.profile.name }}',
            ];

            $filters = [
                'name' => [new TrimStrings(), new LowercaseStrings()],
            ];

            $result = $dto::from($data, $template, $filters);

            expect($result->name)->toBe('john');
        });
    });
});
