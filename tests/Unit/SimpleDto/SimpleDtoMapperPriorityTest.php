<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

describe('SimpleDto Mapper Priority', function(): void {
    describe('Template Priority', function(): void {
        it('template has highest priority over attributes', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ product.product_id }}',
                        'name' => '{{ product.title }}',
                    ];
                }

                public function __construct(
                    #[MapFrom('id')]  // This should be ignored
                    public readonly int $id = 0,

                    #[MapFrom('product_name')]  // This should be ignored
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'id' => 999,  // Should be ignored
                'product_name' => 'Wrong Name',  // Should be ignored
                'product' => [
                    'product_id' => 123,
                    'title' => 'Correct Name',
                ],
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('Correct Name');
        });

        it('template has highest priority over automapping', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ user.user_id }}',
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,
                ) {}
            };

            $data = [
                'id' => 999,  // Should be ignored (automapping)
                'user' => [
                    'user_id' => 123,
                ],
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123);
        });
    });

    describe('Attributes Priority', function(): void {
        it('attributes have priority over automapping when no template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_id')]
                    public readonly int $id = 0,
                ) {}
            };

            $data = [
                'id' => 999,  // Should be ignored (automapping)
                'user_id' => 123,  // Should be used (attribute)
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123);
        });
    });

    describe('Automapping Fallback', function(): void {
        it('uses automapping when no template and no attributes', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'id' => 123,
                'name' => 'John Doe',
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });
    });

    describe('Combined Scenarios', function(): void {
        it('template for some properties, attributes for others', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ user.id }}',
                        'name' => '{{ user_name }}',  // Need to include all properties in template
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,

                    #[MapFrom('user_name')]  // This is ignored because template exists
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'id' => 123,
                ],
                'user_name' => 'John Doe',
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');
        });

        it('template with filters and attributes', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ user.id }}',
                        'name' => '{{ user_name }}',  // Need to include all properties
                    ];
                }

                protected function mapperPipeline(): array
                {
                    return [
                        new TrimStrings(),
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,

                    #[MapFrom('user_name')]  // Ignored because template exists
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'id' => 123,
                ],
                'user_name' => '  John Doe  ',
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe');  // Trimmed by pipeline
        });
    });

    describe('fromArray() Alias', function(): void {
        it('fromArray() uses same logic as fromSource()', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ user.id }}',
                        'name' => '{{ user.name }}',
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'id' => 123,
                    'name' => 'John Doe',
                ],
            ];

            $result1 = $dto::fromSource($data);
            $result2 = $dto::fromArray($data);

            expect($result1->id)->toBe($result2->id)
                ->and($result1->name)->toBe($result2->name);
        });

        it('fromArray() supports all parameters', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'name' => '{{ user.name }}',
                    ];
                }

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'user' => [
                    'name' => 'John',
                    'custom_name' => 'Jane',
                ],
            ];

            // With template override
            $result = $dto::fromArray($data, [
                'name' => '{{ user.custom_name }}',
            ]);

            expect($result->name)->toBe('Jane');
        });
    });

    describe('Complex Nested Scenarios', function(): void {
        it('handles deeply nested data with template', function(): void {
            $dto = new class extends SimpleDto {
                protected function mapperTemplate(): array
                {
                    return [
                        'id' => '{{ data.user.profile.id }}',
                        'email' => '{{ data.user.profile.contact.email }}',
                    ];
                }

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'data' => [
                    'user' => [
                        'profile' => [
                            'id' => 123,
                            'contact' => [
                                'email' => 'john@example.com',
                            ],
                        ],
                    ],
                ],
            ];

            $result = $dto::fromSource($data);

            expect($result->id)->toBe(123)
                ->and($result->email)->toBe('john@example.com');
        });
    });
});
