<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDto;

describe('SimpleDto Complex Data Structures', function(): void {
    describe('Deeply Nested Objects', function(): void {
        it('handles six-level nested structure', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $value = '',
                ) {}
            };

            $data = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'level5' => [
                                    'level6' => [
                                        'value' => 'deep',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'value' => '{{ level1.level2.level3.level4.level5.level6.value }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->value)->toBe('deep');
        });

        it('handles multiple properties from deeply nested structure', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly string $city = '',
                ) {}
            };

            $data = [
                'api' => [
                    'response' => [
                        'data' => [
                            'user' => [
                                'profile' => [
                                    'name' => 'John',
                                    'contact' => [
                                        'email' => 'john@example.com',
                                    ],
                                    'address' => [
                                        'city' => 'Berlin',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'name' => '{{ api.response.data.user.profile.name }}',
                'email' => '{{ api.response.data.user.profile.contact.email }}',
                'city' => '{{ api.response.data.user.profile.address.city }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->name)->toBe('John')
                ->and($result->email)->toBe('john@example.com')
                ->and($result->city)->toBe('Berlin');
        });
    });

    describe('Arrays in Data', function(): void {
        it('handles array with numeric index in template', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $firstName = '',
                ) {}
            };

            $data = [
                'users' => [
                    ['name' => 'John'],
                    ['name' => 'Jane'],
                ],
            ];

            $template = [
                'firstName' => '{{ users.0.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->firstName)->toBe('John');
        });

        it('handles multiple array indices', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $first = '',
                    public readonly string $second = '',
                ) {}
            };

            $data = [
                'items' => [
                    ['value' => 'first'],
                    ['value' => 'second'],
                    ['value' => 'third'],
                ],
            ];

            $template = [
                'first' => '{{ items.0.value }}',
                'second' => '{{ items.1.value }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->first)->toBe('first')
                ->and($result->second)->toBe('second');
        });

        it('handles nested arrays with indices', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $value = '',
                ) {}
            };

            $data = [
                'matrix' => [
                    [
                        ['a', 'b', 'c'],
                        ['d', 'e', 'f'],
                    ],
                    [
                        ['g', 'h', 'i'],
                        ['j', 'k', 'l'],
                    ],
                ],
            ];

            $template = [
                'value' => '{{ matrix.1.0.2 }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->value)->toBe('i');
        });
    });

    describe('Mixed Arrays and Objects', function(): void {
        it('handles array of objects with nested properties', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'users' => [
                    [
                        'profile' => [
                            'contact' => [
                                'email' => 'john@example.com',
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'email' => '{{ users.0.profile.contact.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->email)->toBe('john@example.com');
        });

        it('handles object with array properties', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $firstEmail = '',
                    public readonly string $secondEmail = '',
                ) {}
            };

            $data = [
                'user' => [
                    'emails' => [
                        'john@example.com',
                        'john.doe@example.com',
                    ],
                ],
            ];

            $template = [
                'firstEmail' => '{{ user.emails.0 }}',
                'secondEmail' => '{{ user.emails.1 }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->firstEmail)->toBe('john@example.com')
                ->and($result->secondEmail)->toBe('john.doe@example.com');
        });

        it('handles complex nested structure with arrays and objects', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $productName = '',
                    public readonly string $categoryName = '',
                ) {}
            };

            $data = [
                'store' => [
                    'departments' => [
                        [
                            'name' => 'Electronics',
                            'categories' => [
                                [
                                    'name' => 'Computers',
                                    'products' => [
                                        ['name' => 'Laptop'],
                                        ['name' => 'Desktop'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'productName' => '{{ store.departments.0.categories.0.products.0.name }}',
                'categoryName' => '{{ store.departments.0.categories.0.name }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->productName)->toBe('Laptop')
                ->and($result->categoryName)->toBe('Computers');
        });
    });

    describe('Complex Structures with Filters and Pipeline', function(): void {
        it('applies filters to deeply nested values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'api' => [
                    'response' => [
                        'user' => [
                            'name' => '  JOHN  ',
                        ],
                    ],
                ],
            ];

            $template = [
                'name' => '{{ api.response.user.name }}',
            ];

            $filters = [
                'name' => [new TrimStrings(), new LowercaseStrings()],
            ];

            $result = $dto::from($data, $template, $filters);

            expect($result->name)->toBe('john');
        });

        it('applies pipeline to array-indexed values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $data = [
                'users' => [
                    ['name' => '  john  '],
                ],
            ];

            $template = [
                'name' => '{{ users.0.name }}',
            ];

            $pipeline = [new TrimStrings()];

            $result = $dto::from($data, $template, null, $pipeline);

            expect($result->name)->toBe('john');
        });

        it('applies filters and pipeline to complex nested structure', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'api' => [
                    'users' => [
                        [
                            'contact' => [
                                'email' => '  JOHN@EXAMPLE.COM  ',
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'email' => '{{ api.users.0.contact.email }}',
            ];

            $filters = [
                'email' => new TrimStrings(),
            ];

            $pipeline = [new LowercaseStrings()];

            $result = $dto::from($data, $template, $filters, $pipeline);

            expect($result->email)->toBe('john@example.com');
        });
    });

    describe('Real-World API Response Structures', function(): void {
        it('handles typical REST API response', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $data = [
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => 123,
                        'attributes' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                        ],
                    ],
                ],
            ];

            $template = [
                'id' => '{{ data.user.id }}',
                'name' => '{{ data.user.attributes.name }}',
                'email' => '{{ data.user.attributes.email }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe')
                ->and($result->email)->toBe('john@example.com');
        });

        it('handles GraphQL-style response', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $userName = '',
                    public readonly string $postTitle = '',
                ) {}
            };

            $data = [
                'data' => [
                    'viewer' => [
                        'name' => 'John',
                        'posts' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'title' => 'First Post',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $template = [
                'userName' => '{{ data.viewer.name }}',
                'postTitle' => '{{ data.viewer.posts.edges.0.node.title }}',
            ];

            $result = $dto::from($data, $template);

            expect($result->userName)->toBe('John')
                ->and($result->postTitle)->toBe('First Post');
        });
    });
});
