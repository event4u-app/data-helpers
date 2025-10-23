<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

/**
 * Tests for multi-target mapping functionality.
 *
 * @internal
 */
describe('DataMapper Multi-Target Mapping', function(): void {
    describe('mapToTargetsFromTemplate() - Reverse mapping to multiple targets', function(): void {
        it('writes data to multiple named targets using template aliases', function(): void {
            $userDto = new class {
                public ?string $name = null;

                public ?string $email = null;
            };

            $targets = [
                'user' => $userDto,
                'addr' => [],
            ];

            $template = [
                'profile' => [
                    'fullname' => '{{ user.name }}',
                    'email' => '{{ user.email }}',
                    'street' => '{{ addr.street }}',
                ],
            ];

            $data = [
                'profile' => [
                    'fullname' => 'Alice',
                    'email' => 'alice@example.com',
                    'street' => 'Main Street 1',
                ],
            ];

            $result = DataMapper::source($data)->target($targets)->template($template)->map()->getTarget();

            $user = $result['user'];
            assert(is_object($user));
            $accUser = new DataAccessor($user);
            expect($accUser->get('name'))->toBe('Alice');
            expect($accUser->get('email'))->toBe('alice@example.com');
            expect($result['addr'])->toBe(['street' => 'Main Street 1']);
        });

        it('supports wildcards in reverse mapping', function(): void {
            $targets = [
                'people' => [],
            ];

            $template = [
                'names' => '{{ people.*.name }}',
            ];

            $data = [
                'names' => ['Alice', 'Bob', 'Charlie'],
            ];

            $result = DataMapper::source($data)->target($targets)->template($template)->skipNull(true)->reindexWildcard(
                true
            )->map()->getTarget();

            expect($result['people'])->toBe([
                ['name' => 'Alice'],
                ['name' => 'Bob'],
                ['name' => 'Charlie'],
            ]);
        });
    });

    describe('FluentDataMapper with multi-target support', function(): void {
        it('target() with empty array creates nested structure, NOT multi-target mapping', function(): void {
            // When target is an empty array, it creates a nested array structure

            $source = [
                'profile' => [
                    'fullname' => 'Alice',
                    'email' => 'alice@example.com',
                    'street' => 'Main Street 1',
                ],
            ];

            // Using an empty array as target creates a nested array structure
            $result = DataMapper::source($source)
                ->target([])
                ->template([
                    'user.name' => '{{ profile.fullname }}',
                    'user.email' => '{{ profile.email }}',
                    'addr.street' => '{{ profile.street }}',
                ])
                ->map();

            $target = $result->getTarget();

            // This creates a nested array structure, not separate targets
            expect($target)->toBeArray();
            expect($target)->toHaveKey('user');
            expect($target)->toHaveKey('addr');
            expect($target['user'])->toHaveKey('name');
            expect($target['user']['name'])->toBe('Alice');
            expect($target['addr']['street'])->toBe('Main Street 1');
        });

        it('target() with structured array writes to multiple separate targets', function(): void {
            // Multi-target mapping: Template beschreibt die Zielstruktur
            $userDto = new class {
                public ?string $name = null;

                public ?string $email = null;
            };

            $addrArray = [];

            $source = [
                'fullname' => 'Alice',
                'email' => 'alice@example.com',
                'street' => 'Main Street 1',
            ];

            $result = DataMapper::source($source)
                ->target([
                    'user' => $userDto,
                    'addr' => $addrArray,
                ])
                ->template([
                    'user' => [
                        'name' => '{{ fullname }}',
                        'email' => '{{ email }}',
                    ],
                    'addr' => [
                        'street' => '{{ street }}',
                    ],
                ])
                ->map();

            $targets = $result->getTarget();

            // Mapping schreibt in verschachtelte Struktur
            expect($targets)->toBeArray();
            expect($targets)->toHaveKey('user');
            expect($targets)->toHaveKey('addr');

            $user = $targets['user'];
            expect($user)->toBe($userDto);
            assert(is_object($user));
            $accUser = new DataAccessor($user);
            expect($accUser->get('name'))->toBe('Alice');
            expect($accUser->get('email'))->toBe('alice@example.com');
            expect($targets['addr'])->toBe(['street' => 'Main Street 1']);
        });

        it('structured target mapping works with models and DTOs', function(): void {
            $profileModel = new class {
                public ?string $fullName = null;

                public ?int $age = null;
            };

            $messagesDto = new class {
                /** @var array<int, mixed> */
                public array $items = [];
            };

            $source = [
                'name' => 'Bob',
                'age' => 30,
                'message' => 'Hello World',
            ];

            $result = DataMapper::source($source)
                ->target([
                    'profile' => $profileModel,
                    'messages' => $messagesDto,
                ])
                ->template([
                    'profile' => [
                        'fullName' => '{{ name }}',
                        'age' => '{{ age }}',
                    ],
                    'messages' => [
                        'items' => [
                            '{{ message }}',
                        ],
                    ],
                ])
                ->map();

            $targets = $result->getTarget();

            $profile = $targets['profile'];
            assert(is_object($profile));
            $accProfile = new DataAccessor($profile);
            expect($accProfile->get('fullName'))->toBe('Bob');
            expect($accProfile->get('age'))->toBe(30);

            $messages = $targets['messages'];
            assert(is_object($messages));
            $accMessages = new DataAccessor($messages);
            expect($accMessages->get('items'))->toBe(['Hello World']);
        });

        it('structured target mapping with nested arrays', function(): void {
            $peopleArray = [];
            $statsArray = [];

            $source = [
                'users' => [
                    ['name' => 'Alice', 'age' => 25],
                    ['name' => 'Bob', 'age' => 30],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([
                    'people' => $peopleArray,
                    'stats' => $statsArray,
                ])
                ->template([
                    'people' => [
                        ['name' => '{{ users.0.name }}'],
                        ['name' => '{{ users.1.name }}'],
                    ],
                    'stats' => [
                        ['age' => '{{ users.0.age }}'],
                        ['age' => '{{ users.1.age }}'],
                    ],
                ])
                ->map();

            $targets = $result->getTarget();

            expect($targets['people'])->toBe([
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ]);
            expect($targets['stats'])->toBe([
                ['age' => 25],
                ['age' => 30],
            ]);
        });

        it('can use static mapToTargetsFromTemplate() for reverse multi-target mapping', function(): void {
            // For reverse multi-target mapping, use the static method directly
            $userDto = new class {
                public ?string $name = null;

                public ?string $email = null;
            };

            $targets = [
                'user' => $userDto,
                'addr' => [],
            ];

            $template = [
                'profile' => [
                    'fullname' => '{{ user.name }}',
                    'email' => '{{ user.email }}',
                    'street' => '{{ addr.street }}',
                ],
            ];

            $data = [
                'profile' => [
                    'fullname' => 'Alice',
                    'email' => 'alice@example.com',
                    'street' => 'Main Street 1',
                ],
            ];

            // Use Fluent API for reverse multi-target mapping
            $result = DataMapper::source($data)->target($targets)->template($template)->map()->getTarget();

            $user = $result['user'];
            assert(is_object($user));
            $accUser = new DataAccessor($user);
            expect($accUser->get('name'))->toBe('Alice');
            expect($accUser->get('email'))->toBe('alice@example.com');
            expect($result['addr'])->toBe(['street' => 'Main Street 1']);
        });
    });

    describe('mapFromTemplate() - Forward mapping from multiple sources', function(): void {
        it('reads from multiple named sources using template', function(): void {
            $userModel = new class {
                public string $name = 'Alice';

                public string $email = 'alice@example.com';
            };

            $addressArray = [
                'street' => 'Main Street 1',
                'zip' => '10115',
            ];

            $sources = [
                'user' => $userModel,
                'addr' => $addressArray,
            ];

            $template = [
                'profile' => [
                    'fullname' => '{{ @user.name }}',
                    'email' => '{{ @user.email }}',
                    'address' => [
                        'street' => '{{ @addr.street }}',
                        'zip' => '{{ @addr.zip }}',
                    ],
                ],
            ];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result)->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'email' => 'alice@example.com',
                    'address' => [
                        'street' => 'Main Street 1',
                        'zip' => '10115',
                    ],
                ],
            ]);
        });

        it('FluentDataMapper DOES support mapFromTemplate via source structure', function(): void {
            // When source is an array with named keys, and template uses those keys,
            // it works like mapFromTemplate()

            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
                'addr' => [
                    'street' => 'Main Street 1',
                    'zip' => '10115',
                ],
            ];

            $result = DataMapper::source($source)
                ->template([
                    'profile' => [
                        'fullname' => '{{ user.name }}',
                        'email' => '{{ user.email }}',
                        'address' => [
                            'street' => '{{ addr.street }}',
                            'zip' => '{{ addr.zip }}',
                        ],
                    ],
                ])
                ->map();

            expect($result->toArray())->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'email' => 'alice@example.com',
                    'address' => [
                        'street' => 'Main Street 1',
                        'zip' => '10115',
                    ],
                ],
            ]);
        });
    });

    describe('Query Builder equivalents in new API', function(): void {
        it('FluentDataMapper->query() provides query builder for wildcard paths', function(): void {
            // DataMapper::query() ist ein STANDALONE Query Builder
            // FluentDataMapper->query() ist für Wildcard-Pfade WÄHREND des Mappings

            $products = [
                ['name' => 'Laptop', 'price' => 1200],
                ['name' => 'Mouse', 'price' => 25],
            ];

            // Query auf Wildcard-Pfad
            $mapper = DataMapper::source(['products' => $products])
                ->template([
                    'items' => '{{ products.* }}',
                ])
                ->query('products.*')
                ->where('price', '>', 100)
                ->end();

            // Query ist konfiguriert
            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });

        it('pipeline() + query() combination works', function(): void {
            // pipeQuery-Äquivalent: pipeline() + query()

            $products = [
                ['name' => '  Laptop  ', 'price' => 1200],
            ];

            $mapper = DataMapper::source(['products' => $products])
                ->pipeline([
                    new TrimStrings(),
                ])
                ->template([
                    'items' => '{{ products.* }}',
                ])
                ->query('products.*')
                ->where('price', '>', 100)
                ->end();

            // Pipeline + Query sind konfiguriert
            expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
        });
    });
});
