<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\SimpleDto;

describe('SimpleDto Template Syntax Variations', function(): void {
    describe('Single Placeholder Templates', function(): void {
        it('handles simple placeholder', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = ['name' => 'John'];

            $template = [
                'name' => '{{ name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John');
        });

        it('handles nested placeholder', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John');
        });

        it('handles deeply nested placeholder', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'user' => [
                    'contact' => [
                        'email' => 'john@example.com',
                    ],
                ],
            ];

            $template = [
                'email' => '{{ user.contact.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->email)->toBe('john@example.com');
        });
    });

    describe('Multiple Properties from Same Source', function(): void {
        it('maps multiple properties from nested object', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $firstName = '',
                    public readonly string $lastName = '',
                ) {}
            };

            $data = [
                'user' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ];

            $template = [
                'firstName' => '{{ user.firstName }}',
                'lastName' => '{{ user.lastName }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->firstName)->toBe('John')
                ->and($result->lastName)->toBe('Doe');
        });

        it('maps properties from different nested objects', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly string $city = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                ],
                'address' => [
                    'city' => 'Berlin',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
                'city' => '{{ address.city }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John')
                ->and($result->city)->toBe('Berlin');
        });
    });

    describe('Template with Filters and Pipeline', function(): void {
        it('applies filters to nested template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => '  john  ',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
            ];

            $filters = [
                'name' => new TrimStrings(),
            ];

            $result = $dto::from($data, $template, $filters);

            expect($result->name)->toBe('john');
        });

        it('applies pipeline to nested template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'john',
                ],
            ];

            $template = [
                'name' => '{{ user.name }}',
            ];

            $pipeline = [new UppercaseStrings()];

            $result = $dto::from($data, $template, null, $pipeline);

            expect($result->name)->toBe('JOHN');
        });

        it('applies both filters and pipeline to nested template', function(): void {
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

            $pipeline = [new LowercaseStrings()];

            $result = $dto::from($data, $template, $filters, $pipeline);

            expect($result->name)->toBe('john');
        });
    });

    describe('Plain Text Templates', function(): void {
        it('handles template with only text (no placeholders)', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $status = '',
                ) {}
            };

            $data = ['other' => 'value'];

            $template = [
                'status' => 'active',
            ];

            $result = $dto::from($data, $template);

            expect($result->status)->toBe('active');
        });

        it('handles mix of placeholder and plain text templates', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly string $status = '',
                ) {}
            };

            $data = ['name' => 'John'];

            $template = [
                'name' => '{{ name }}',
                'status' => 'active',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John')
                ->and($result->status)->toBe('active');
        });
    });

    describe('Template with Different Data Types', function(): void {
        it('handles integer values in template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $age = 0,
                ) {}
            };

            $data = [
                'user' => [
                    'age' => 30,
                ],
            ];

            $template = [
                'age' => '{{ user.age }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->age)->toBe(30);
        });

        it('handles boolean values in template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly bool $active = false,
                ) {}
            };

            $data = [
                'user' => [
                    'active' => true,
                ],
            ];

            $template = [
                'active' => '{{ user.active }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->active)->toBeTrue();
        });

        it('handles float values in template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly float $price = 0.0,
                ) {}
            };

            $data = [
                'product' => [
                    'price' => 19.99,
                ],
            ];

            $template = [
                'price' => '{{ product.price }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->price)->toBe(19.99);
        });
    });
});
