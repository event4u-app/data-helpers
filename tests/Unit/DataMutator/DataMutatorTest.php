<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMutator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

describe('DataMutator', function(): void {
    describe('Array mutations - Single values', function(): void {
        test('can set simple values in empty array', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('name', 'Alice')->toArray();

            expect($result)->toBe([
                'name' => 'Alice',
            ]);
        });

        test('can set nested values in empty array', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('user.profile.name', 'Alice')->toArray();

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                    ],
                ],
            ]);
        });

        test('can set values in existing array', function(): void {
            $data = [
                'existing' => 'value',
            ];
            $result = DataMutator::make($data)->set('name', 'Alice')->toArray();

            expect($result)->toBe([
                'existing' => 'value',
                'name' => 'Alice',
            ]);
        });

        test('can overwrite existing values', function(): void {
            $data = [
                'name' => 'Bob',
            ];
            $result = DataMutator::make($data)->set('name', 'Alice')->toArray();

            expect($result)->toBe([
                'name' => 'Alice',
            ]);
        });

        test('can set deeply nested values', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('level1.level2.level3.level4.value', 'deep')->toArray();

            expect($result)->toBe([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'value' => 'deep',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('can overwrite scalar with nested structure', function(): void {
            $data = [
                'user' => 'scalar_value',
            ];
            $result = DataMutator::make($data)->set('user.profile.name', 'Alice')->toArray();

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                    ],
                ],
            ]);
        });
    });

    describe('Array mutations - Multiple values', function(): void {
        test('can set multiple values with array of paths', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set([
                'users.0.name' => 'Alice', 'users.1.name' => 'Bob',
                'users.1.age' => 25,
            ])->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'name' => 'Alice',
                    ],
                    [
                        'name' => 'Bob',
                        'age' => 25,
                    ],
                ],
            ]);
        });

        test('can set multiple nested values', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set([
                'config.database.host' => 'localhost', 'config.database.port' => 3306,
                'config.cache.driver' => 'redis',
                'config.cache.ttl' => 3600,
            ])->toArray();

            expect($result)->toBe([
                'config' => [
                    'database' => [
                        'host' => 'localhost',
                        'port' => 3306,
                    ],
                    'cache' => [
                        'driver' => 'redis',
                        'ttl' => 3600,
                    ],
                ],
            ]);
        });

        test('can overwrite existing values with multiple paths', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'OldAlice',
                        'age' => 20,
                    ],
                    [
                        'name' => 'OldBob',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set([
                'users.0.name' => 'NewAlice', 'users.0.email' => 'alice@example.com',
                'users.1.age' => 30,
            ])->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'name' => 'NewAlice',
                        'age' => 20,
                        'email' => 'alice@example.com',
                    ],
                    [
                        'name' => 'OldBob',
                        'age' => 30,
                    ],
                ],
            ]);
        });

        test('can mix simple and nested paths in multiple assignment', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set([
                'name' => 'Alice', 'profile.age' => 30,
                'profile.address.city' => 'Berlin',
                'profile.address.country' => 'Germany',
                'settings.theme' => 'dark',
            ])->toArray();

            expect($result)->toBe([
                'name' => 'Alice',
                'profile' => [
                    'age' => 30,
                    'address' => [
                        'city' => 'Berlin',
                        'country' => 'Germany',
                    ],
                ],
                'settings' => [
                    'theme' => 'dark',
                ],
            ]);
        });

        test('can set empty array with multiple paths', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set([])->toArray();

            expect($result)->toBe([]);
        });

        test('can set multiple values with special types', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set([
                'string' => 'text', 'number' => 42,
                'float' => 19.99,
                'boolean' => true,
                'null' => null,
                'array' => ['a', 'b', 'c'],
            ])->toArray();

            expect($result)->toBe([
                'string' => 'text',
                'number' => 42,
                'float' => 19.99,
                'boolean' => true,
                'null' => null,
                'array' => ['a', 'b', 'c'],
            ]);
        });
    });

    describe('Array mutations - Merge functionality', function(): void {
        test('can merge arrays without merge flag (overwrites)', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::make($data)->set('user', [
                'age' => 30,
            ])->toArray();

            expect($result)->toBe([
                'user' => [
                    'age' => 30,
                ],
            ]);
        });

        test('can merge arrays with merge flag', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::make($data)->set('user', [
                'age' => 30,
            ], true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ]);
        });

        test('can perform deep merge', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('user', [
                'age' => 30,
                'profile' => [
                    'zip' => '10115',
                ],
            ], true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                        'zip' => '10115',
                    ],
                    'age' => 30,
                ],
            ]);
        });

        test('can merge multiple levels deep', function(): void {
            $data = [
                'config' => [
                    'database' => [
                        'host' => 'localhost',
                        'connections' => [
                            'mysql' => [
                                'port' => 3306,
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('config', [
                'database' => [
                    'name' => 'myapp',
                    'connections' => [
                        'mysql' => [
                            'charset' => 'utf8',
                        ],
                        'redis' => [
                            'port' => 6379,
                        ],
                    ],
                ],
                'cache' => [
                    'driver' => 'redis',
                ],
            ], true)->toArray();

            expect($result)->toBe([
                'config' => [
                    'database' => [
                        'host' => 'localhost',
                        'connections' => [
                            'mysql' => [
                                'port' => 3306,
                                'charset' => 'utf8',
                            ],
                            'redis' => [
                                'port' => 6379,
                            ],
                        ],
                        'name' => 'myapp',
                    ],
                    'cache' => [
                        'driver' => 'redis',
                    ],
                ],
            ]);
        });

        test('merge overwrites scalar values', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'age' => 25,
                ],
            ];
            $result = DataMutator::make($data)->set('user', [
                'age' => 30,
            ], true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30, // overwritten
                ],
            ]);
        });

        test('merge with non-array values falls back to overwrite', function(): void {
            $data = [
                'user' => 'Alice',
            ];
            $result = DataMutator::make($data)->set('user', [
                'age' => 30,
            ], true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'age' => 30,
                ],
            ]);
        });

        test('merge with non-array new value falls back to overwrite', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::make($data)->set('user', 'Bob', true)->toArray();

            expect($result)->toBe([
                'user' => 'Bob',
            ]);
        });

        test('can merge empty arrays', function(): void {
            $data = [
                'user' => [],
            ];
            $result = DataMutator::make($data)->set('user', [
                'name' => 'Alice',
            ], true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('can merge into empty target', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('user', [
                'name' => 'Alice',
            ], true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('numeric indices are replaced, not merged', function(): void {
            $data = [
                'tags' => ['old', 'keep'],
            ];
            $result = DataMutator::make($data)->set('tags', ['new'], true)->toArray();

            expect($result)->toBe([
                'tags' => ['new', 'keep'],
            ]);
        });

        test('numeric indices replacement with multiple values', function(): void {
            $data = [
                'items' => ['a', 'b', 'c', 'd'],
            ];
            $result = DataMutator::make($data)->set('items', ['x', 'y'], true)->toArray();

            expect($result)->toBe([
                'items' => ['x', 'y', 'c', 'd'],
            ]);
        });

        test('numeric indices replacement preserves higher indices', function(): void {
            $data = [
                'list' => [
                    0 => 'zero',
                    1 => 'one',
                    2 => 'two',
                    5 => 'five',
                ],
            ];
            $result = DataMutator::make($data)->set('list', [
                0 => 'NEW_ZERO',
                2 => 'NEW_TWO',
            ], true)->toArray();

            expect($result)->toBe([
                'list' => [
                    0 => 'NEW_ZERO',
                    1 => 'one',
                    2 => 'NEW_TWO',
                    5 => 'five',
                ],
            ]);
        });

        test('mixed associative and numeric keys merge correctly', function(): void {
            $data = [
                'config' => [
                    'name' => 'app',
                    'tags' => ['old1', 'old2'],
                    'version' => '1.0',
                ],
            ];
            $result = DataMutator::make($data)->set('config', [
                'tags' => ['new1'],
                'description' => 'My App',
            ], true)->toArray();

            expect($result)->toBe([
                'config' => [
                    'name' => 'app',
                    'tags' => ['new1', 'old2'],
                    'version' => '1.0',
                    'description' => 'My App',
                ],
            ]);
        });

        test('deep merge with numeric indices in nested arrays', function(): void {
            $data = [
                'users' => [
                    'alice' => [
                        'permissions' => ['read', 'write'],
                        'roles' => ['user'],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('users', [
                'alice' => [
                    'permissions' => ['admin'],
                    'email' => 'alice@example.com',
                ],
            ], true)->toArray();

            expect($result)->toBe([
                'users' => [
                    'alice' => [
                        'permissions' => ['admin', 'write'],
                        'roles' => ['user'],
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        test('empty numeric array merge', function(): void {
            $data = [
                'items' => [],
            ];
            $result = DataMutator::make($data)->set('items', ['first'], true)->toArray();

            expect($result)->toBe([
                'items' => ['first'],
            ]);
        });

        test('numeric merge with non-sequential indices', function(): void {
            $data = [
                'sparse' => [
                    0 => 'zero',
                    3 => 'three',
                    7 => 'seven',
                ],
            ];
            $result = DataMutator::make($data)->set('sparse', [
                1 => 'one',
                3 => 'NEW_THREE',
            ], true)->toArray();

            expect($result)->toBe([
                'sparse' => [
                    0 => 'zero',
                    3 => 'NEW_THREE',
                    7 => 'seven',
                    1 => 'one',
                ],
            ]);
        });
    });

    describe('Array mutations with wildcards', function(): void {
        test('can set values with single wildcard', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => '',
                    ],
                    [
                        'name' => '',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('users.*.name', 'Bob')->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'name' => 'Bob',
                    ],
                    [
                        'name' => 'Bob',
                    ],
                ],
            ]);
        });

        test('can set values with wildcard at root level', function(): void {
            $data = ['', '', ''];
            $result = DataMutator::make($data)->set('*', 'value')->toArray();

            expect($result)->toBe(['value', 'value', 'value']);
        });

        test('can set nested values with wildcards', function(): void {
            $data = [
                'orders' => [
                    [
                        'items' => [
                            [
                                'price' => 0,
                            ],
                            [
                                'price' => 0,
                            ],
                        ],
                    ],
                    [
                        'items' => [
                            [
                                'price' => 0,
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('orders.*.items.*.price', 100)->toArray();

            expect($result)->toBe([
                'orders' => [
                    [
                        'items' => [
                            [
                                'price' => 100,
                            ],
                            [
                                'price' => 100,
                            ],
                        ],
                    ],
                    [
                        'items' => [
                            [
                                'price' => 100,
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('wildcard on empty array does nothing', function(): void {
            $data = [
                'users' => [],
            ];
            $result = DataMutator::make($data)->set('users.*.name', 'Bob')->toArray();

            expect($result)->toBe([
                'users' => [],
            ]);
        });

        test('wildcard on non-array elements is ignored', function(): void {
            $data = [
                'items' => [
                    'string_value',
                    [
                        'name' => 'object',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('items.*.name', 'test')->toArray();

            expect($result)->toBe([
                'items' => [
                    'string_value', // unchanged
                    [
                        'name' => 'test',
                    ], // changed
                ],
            ]);
        });

        test('can merge arrays with wildcards', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                        'profile' => [
                            'city' => 'Berlin',
                        ],
                    ],
                    [
                        'name' => 'Bob',
                        'profile' => [
                            'city' => 'Munich',
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('users.*', [
                'profile' => [
                    'country' => 'Germany',
                ],
            ], true)->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'name' => 'Alice',
                        'profile' => [
                            'city' => 'Berlin',
                            'country' => 'Germany',
                        ],
                    ],
                    [
                        'name' => 'Bob',
                        'profile' => [
                            'city' => 'Munich',
                            'country' => 'Germany',
                        ],
                    ],
                ],
            ]);
        });

        test('wildcard merge with non-array elements falls back to overwrite', function(): void {
            $data = [
                'items' => [
                    'string_value',
                    [
                        'name' => 'object',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('items.*', [
                'new' => 'value',
            ], true)->toArray();

            expect($result)->toBe([
                'items' => [
                    [
                        'new' => 'value',
                    ], // overwritten
                    [
                        'name' => 'object',
                        'new' => 'value',
                    ], // merged
                ],
            ]);
        });

        test('wildcard merge with numeric indices', function(): void {
            $data = [
                'groups' => [
                    [
                        'tags' => ['old1', 'old2'],
                        'name' => 'Group1',
                    ],
                    [
                        'tags' => ['old3'],
                        'name' => 'Group2',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('groups.*', [
                'tags' => ['new1'],
                'active' => true,
            ], true)->toArray();

            expect($result)->toBe([
                'groups' => [
                    [
                        'tags' => ['new1', 'old2'],
                        'name' => 'Group1',
                        'active' => true,
                    ],
                    [
                        'tags' => ['new1'],
                        'name' => 'Group2',
                        'active' => true,
                    ],
                ],
            ]);
        });
    });

    describe('Object mutations - DTOs', function(): void {
        test('can set public properties on DTO', function(): void {
            $dto = new class {
                public string $name = '';
                public int $age = 0;
            };

            DataMutator::make($dto)->set('name', 'Charlie');

            expect($dto->name)->toBe('Charlie');
            expect($dto->age)->toBe(0); // unchanged
        });

        test('can set multiple properties on DTO', function(): void {
            $dto = new class {
                public string $name = '';
                public int $age = 0;
                public string $email = '';

                /** @var array<string, mixed> */
                public array $config = [];
            };
            DataMutator::make($dto)->set([
                'name' => 'Alice', 'age' => 30,
                'email' => 'alice@example.com',

                'config.theme' => 'dark',
                'config.language' => 'en',
            ]);

            expect($dto->name)->toBe('Alice');
            expect($dto->age)->toBe(30);
            expect($dto->email)->toBe('alice@example.com');
            expect($dto->config)->toBe([
                'theme' => 'dark',
                'language' => 'en',
            ]);
        });

        test('can set private properties on DTO using reflection', function(): void {
            $dto = new class {
                private string $name = '';

                public function getName(): string
                {
                    return $this->name;
                }
            };

            DataMutator::make($dto)->set('name', 'Charlie');

            expect($dto->getName())->toBe('Charlie');
        });

        test('can create dynamic properties on DTO', function(): void {
            $dto = new #[AllowDynamicProperties] class {
            };

            DataMutator::make($dto)->set('dynamicProperty', 'value');

            /** @phpstan-ignore-next-line unknown */
            expect($dto->dynamicProperty)->toBe('value');
        });

        test('can set nested values in DTO properties', function(): void {
            $dto = new class {
                /** @var array<string, mixed> */
                public array $config = [];
            };

            DataMutator::make($dto)->set('config.database.host', 'localhost');

            expect($dto->config)->toBe([
                'database' => [
                    'host' => 'localhost',
                ],
            ]);
        });

        test('can merge arrays in DTO properties', function(): void {
            $dto = new class {
                /** @var array<string, mixed> */
                public array $config = [
                    'database' => [
                        'host' => 'localhost',
                    ],
                ];
            };

            DataMutator::make($dto)->set('config', [
                'database' => [
                    'port' => 3306,
                ],
                'cache' => [
                    'driver' => 'redis',
                ],
            ], true);

            expect($dto->config)->toBe([
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306,
                ],
                'cache' => [
                    'driver' => 'redis',
                ],
            ]);
        });
    });

    describe('Laravel Model mutations', function(): void {
        test('can set attributes on Laravel model', function(): void {
            $model = new class extends Model {
                protected $fillable = ['name', 'email'];
            };

            DataMutator::make($model)->set('name', 'Alice');

            expect($model->getAttribute('name'))->toBe('Alice');
        });

        test('can set multiple attributes on Laravel model', function(): void {
            $model = new class extends Model {
                protected $fillable = ['name', 'email'];
            };

            $result1 = DataMutator::make($model)->set('name', 'Alice')->toArray();
            $result2 = DataMutator::make($result1)->set('email', 'alice@example.com')->toArray();

            expect($model->getAttribute('name'))->toBe('Alice');
            expect($model->getAttribute('email'))->toBe('alice@example.com');
        });

        test('can set multiple attributes at once on Laravel model', function(): void {
            $model = new class extends Model {
                protected $fillable = ['name', 'email', 'age', 'active'];
            };

            DataMutator::make($model)->set([
                'name' => 'Bob', 'email' => 'bob@example.com',
                'age' => 25,
                'active' => true,
            ]);

            expect($model->getAttribute('name'))->toBe('Bob');
            expect($model->getAttribute('email'))->toBe('bob@example.com');
            expect($model->getAttribute('age'))->toBe(25);
            expect($model->getAttribute('active'))->toBe(true);
        });
    })->group('laravel');

    describe('Arrayable object mutations', function(): void {
        test('converts Arrayable to array and sets values', function(): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Bob',
                        'age' => 25,
                    ];
                }
            };

            $result = DataMutator::make($arrayable)->set('name', 'Alice')->toArray();

            expect($result)->toBeArray();
            expect($result)->toBe([
                'name' => 'Alice',
                'age' => 25,
            ]);
        });

        test('can set nested values in Arrayable', function(): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'user' => [
                            'profile' => [
                                'name' => 'Bob',
                            ],
                        ],
                    ];
                }
            };

            $result = DataMutator::make($arrayable)->set('user.profile.age', 30)->toArray();

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Bob',
                        'age' => 30,
                    ],
                ],
            ]);
        });

        test('can set multiple values in Arrayable', function(): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Bob',
                        'age' => 25,
                    ];
                }
            };

            $result = DataMutator::make($arrayable)->set([
                'name' => 'Alice', 'age' => 30,
                'email' => 'alice@example.com',
                'profile.city' => 'Berlin',
            ])->toArray();

            expect($result)->toBe([
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
                'profile' => [
                    'city' => 'Berlin',
                ],
            ]);
        });

        test('can merge arrays in Arrayable objects', function(): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Bob',
                        'profile' => [
                            'city' => 'Munich',
                        ],
                    ];
                }
            };

            $result = DataMutator::make($arrayable)->set('profile', [
                'country' => 'Germany',
            ], true)->toArray();

            expect($result)->toBe([
                'name' => 'Bob',
                'profile' => [
                    'city' => 'Munich',
                    'country' => 'Germany',
                ],
            ]);
            /** @return array<string, mixed> */
        });
    })->group('laravel');

    describe('JsonSerializable object mutations', function(): void {
        test('converts JsonSerializable to array and sets values', function(): void {
            $jsonSerializable = new class implements JsonSerializable {
                /** @return array<string, mixed> */
                public function jsonSerialize(): array
                {
                    return [
                        'name' => 'Bob',
                        'age' => 25,
                    ];
                }
            };

            $result = DataMutator::make($jsonSerializable)->set('name', 'Alice')->toArray();

            /** @return array<string, mixed> */
            expect($result)->toBeArray();
            expect($result)->toBe([
                'name' => 'Alice',
                'age' => 25,
            ]);
        });

        test('can set multiple values in JsonSerializable', function(): void {
            $jsonSerializable = new class implements JsonSerializable {
                /** @return array<string, mixed> */
                public function jsonSerialize(): array
                {
                    return [
                        'name' => 'Bob',
                        'age' => 25,
                    ];
                }
            };

            $result = DataMutator::make($jsonSerializable)->set([
                'name' => 'Charlie', 'age' => 35,
                'location.city' => 'Munich',
                'location.country' => 'Germany',
                /** @return array<string, mixed> */
            ])->toArray();

            expect($result)->toBe([
                'name' => 'Charlie',
                'age' => 35,
                'location' => [
                    'city' => 'Munich',
                    'country' => 'Germany',
                ],
            ]);
        });

        test('can merge arrays in JsonSerializable objects', function(): void {
            $jsonSerializable = new class implements JsonSerializable {
                /** @return array<string, mixed> */
                public function jsonSerialize(): array
                {
                    return [
                        'name' => 'David',
                        'settings' => [
                            'theme' => 'dark',
                        ],
                    ];
                }
            };

            $result = DataMutator::make($jsonSerializable)->set('settings', [
                'language' => 'en',
            ], true)->toArray();

            expect($result)->toBe([
                'name' => 'David',
                'settings' => [
                    'theme' => 'dark',
                    'language' => 'en',
                ],
            ]);
        });
    });

    describe('Collection mutations', function(): void {
        test('can set values in Collections', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
            $result = DataMutator::make($collection)->set('0.age', 30)->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'name' => 'Alice',
                    'age' => 30,
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
        });

        test('can set values with wildcards in Collections', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
            $result = DataMutator::make($collection)->set('*.age', 25)->toArray();
            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'name' => 'Alice',

                    'age' => 25,
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
        });

        test('can merge values in Collections', function(): void {
            $collection = collect([
                [
                    'config' => [
                        'debug' => true,
                    ],
                ],
            ]);
            $result = DataMutator::make($collection)->merge('0.config', [
                'cache' => 'redis',
            ])->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'config' => [
                        'debug' => true,
                        'cache' => 'redis',
                    ],
                ],
            ]);
        });

        test('can set multiple values in Collections', function(): void {
            $collection = collect([[], []]);
            $result = DataMutator::make($collection)->set([
                '0.name' => 'Alice', '1.name' => 'Bob',
                '1.age' => 25,
            ])->toArray();

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result)->toBeInstanceOf(Collection::class);

            expect($result->toArray())->toBe([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
        });

        test('can merge with numeric indices in Collections', function(): void {
            $collection = collect([
                [
                    'tags' => ['old1', 'old2'],
                ],
            ]);
            $result = DataMutator::make($collection)->merge('0.tags', ['new1'])->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'tags' => ['new1', 'old2'],
                ],
            ]);
        });
    })->group('laravel');

    describe('Special value types', function(): void {
        test('can set null values', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('value', null)->toArray();

            expect($result)->toBe([
                'value' => null,
            ]);
        });

        test('can set boolean values', function(): void {
            $data = [];
            $result1 = DataMutator::make($data)->set('enabled', true)->toArray();
            $result2 = DataMutator::make($result1)->set('disabled', false)->toArray();

            expect($result2)->toBe([
                'enabled' => true,
                'disabled' => false,
            ]);
        });

        test('can set numeric values', function(): void {
            $data = [];
            $result1 = DataMutator::make($data)->set('count', 42)->toArray();
            $result2 = DataMutator::make($result1)->set('price', 19.99)->toArray();

            expect($result2)->toBe([
                'count' => 42,
                'price' => 19.99,
            ]);
        });

        test('can set array values', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('items', ['a', 'b', 'c'])->toArray();

            expect($result)->toBe([
                'items' => ['a', 'b', 'c'],
            ]);
        });

        test('can set object values', function(): void {
            $data = [];
            $obj = new stdClass();
            $obj->prop = 'value';

            $result = DataMutator::make($data)->set('object', $obj)->toArray();

            /** @var array<string, mixed> $result */
            assert(is_array($result));
            expect($result['object'])->toBe($obj);
        });
    });

    describe('Edge cases', function(): void {
        test('handles empty path segments gracefully', function(): void {
            $data = [];
            $result = DataMutator::make($data)->set('', 'value')->toArray();

            // Empty path is ignored by DotPathHelper (segments('') => []); result remains unchanged
            expect($result)->toBe([]);
        });

        test('handles paths with consecutive dots', function(): void {
            $data = [];

            expect(fn(): DataMutator => DataMutator::make($data)->set('a..b', 'value'))
                ->toThrow(InvalidArgumentException::class, 'Invalid dot-path syntax: double dot in "a..b"');
        });

        test('can set values in mixed object-array structures', function(): void {
            $dto = new class {
                /** @var array<int, array<string, mixed>> */
                public array $items = [
                    [
                        'name' => 'item1',
                    ],
                    [
                        'name' => 'item2',
                    ],
                ];
            };

            DataMutator::make($dto)->set('items.0.price', 100);

            expect($dto->items[0])->toBe([
                'name' => 'item1',
                'price' => 100,
            ]);
            expect($dto->items[1])->toBe([
                'name' => 'item2',
            ]);
        });

        test('can handle null properties in objects', function(): void {
            $dto = new class {
                /** @var null|array<string, mixed> */
                public ?array $config = null;
            };

            DataMutator::make($dto)->set('config.database.host', 'localhost');

            expect($dto->config)->toBe([
                'database' => [
                    'host' => 'localhost',
                ],
            ]);
        });

        test('wildcard with mixed object types in array', function(): void {
            $data = [
                'items' => [
                    new class {
                        public string $name = 'obj1';
                    },
                    [
                        'name' => 'array1',
                    ],
                    new class {
                        public string $name = 'obj2';
                    },
                ],
            ];

            $result = DataMutator::make($data)->set('items.*.name', 'updated')->toArray();

            /** @var array{items: array<int, mixed>} $result */
            assert(is_array($result));

            /** @var object $o0 */
            $o0 = $result['items'][0];

            /** @var array<string, mixed> $a1 */
            $a1 = $result['items'][1];

            /** @var object $o2 */
            $o2 = $result['items'][2];

            /** @phpstan-ignore-next-line unknown */
            expect($o0->name)->toBe('updated');
            expect($a1['name'])->toBe('updated');
            /** @phpstan-ignore-next-line unknown */
            expect($o2->name)->toBe('updated');
        });

        test('can combine wildcards with multiple value assignment', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => '',
                        'active' => false,
                    ],
                    [
                        'name' => '',
                        'active' => false,
                    ],
                ],
                'config' => [],
            ];

            $result = DataMutator::make($data)->set([
                'users.*.name' => 'DefaultUser', 'users.*.active' => true,
                'config.theme' => 'dark',
                'config.language' => 'en',
            ])->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'name' => 'DefaultUser',
                        'active' => true,
                    ],
                    [
                        'name' => 'DefaultUser',
                        'active' => true,
                    ],
                ],
                'config' => [
                    'theme' => 'dark',
                    'language' => 'en',
                ],
            ]);
        });

        test('multiple values with overlapping paths', function(): void {
            $data = [];

            $result = DataMutator::make($data)->set([
                'user.profile.name' => 'Alice', 'user.profile.age' => 30,
                'user.settings.theme' => 'dark',
                'user.permissions.admin' => true,
            ])->toArray();

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'age' => 30,
                    ],
                    'settings' => [
                        'theme' => 'dark',
                    ],
                    'permissions' => [
                        'admin' => true,
                    ],
                ],
            ]);
        });

        test('multiple values can overwrite each other', function(): void {
            $data = [];

            $result = DataMutator::make($data)->set([
                'value' => 'second', 'nested.value' => 'nested_second', // This should overwrite too
            ])->toArray();

            expect($result)->toBe([
                'value' => 'second',
                'nested' => [
                    'value' => 'nested_second',
                ],
            ]);
        });

        test('can use merge with multiple values', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
                'config' => [
                    'theme' => 'light',
                ],
            ];

            $result = DataMutator::make($data)->set([
                'user' => [
                    'age' => 30, ],
                'config' => [
                    'language' => 'en',
                ],
            ], null, true)->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
                'config' => [
                    'theme' => 'light',
                    'language' => 'en',
                ],
            ]);
        });

        test('merge with multiple values and deep structures', function(): void {
            $data = [
                'app' => [
                    'database' => [
                        'host' => 'localhost',
                    ],
                    'cache' => [
                        'driver' => 'file',
                    ],
                ],
            ];

            $result = DataMutator::make($data)->set([
                'app' => [
                    'database' => [
                        'port' => 3306, ],
                    'mail' => [
                        'driver' => 'smtp',
                    ],
                ],
            ], null, true)->toArray();

            expect($result)->toBe([
                'app' => [
                    'database' => [
                        'host' => 'localhost',
                        'port' => 3306,
                    ],
                    'cache' => [
                        'driver' => 'file',
                    ],
                    'mail' => [
                        'driver' => 'smtp',
                    ],
                ],
            ]);
        });

        test('multiple values merge with numeric indices', function(): void {
            $data = [
                'lists' => [
                    'tags' => ['old1', 'old2'],
                    'categories' => ['cat1', 'cat2', 'cat3'],
                ],
            ];

            $result = DataMutator::make($data)->set([
                'lists' => [
                    'tags' => ['new1'], 'categories' => ['newcat1', 'newcat2'],
                ],
            ], null, true)->toArray();

            expect($result)->toBe([
                'lists' => [
                    'tags' => ['new1', 'old2'],
                    'categories' => ['newcat1', 'newcat2', 'cat3'],
                ],
            ]);
        });

        test('complex merge with mixed associative and numeric keys', function(): void {
            $data = [
                'system' => [
                    'modules' => ['auth', 'cache'],
                    'config' => [
                        'debug' => true,
                        'features' => ['feature1', 'feature2'],
                    ],
                ],
            ];

            $result = DataMutator::make($data)->set([
                'system' => [
                    'modules' => ['logging'], 'config' => [
                        'features' => ['newfeature'],
                        'version' => '2.0',
                    ],
                ],
            ], null, true)->toArray();

            expect($result)->toBe([
                'system' => [
                    'modules' => ['logging', 'cache'],
                    'config' => [
                        'debug' => true,
                        'features' => ['newfeature', 'feature2'],
                        'version' => '2.0',
                    ],
                ],
            ]);
        });

        test('numeric merge with string keys that look like numbers', function(): void {
            $data = [
                'mixed' => [
                    /** @phpstan-ignore-next-line unknown */
                    '0' => 'string_zero',
                    0 => 'int_zero',
                    /** @phpstan-ignore-next-line unknown */
                    '1' => 'string_one',

                    1 => 'int_one',
                ],
            ];

            $result = DataMutator::make($data)->set('mixed', [
                /** @phpstan-ignore-next-line unknown */
                '0' => 'new_string_zero',
                0 => 'new_int_zero',
            ], true)->toArray();

            expect($result)->toBe([
                'mixed' => [
                    /** @phpstan-ignore-next-line unknown */
                    '0' => 'new_string_zero',

                    0 => 'new_int_zero',
                    /** @phpstan-ignore-next-line unknown */
                    '1' => 'string_one',

                    1 => 'int_one',
                ],
            ]);
        });

        test('numeric merge replaces array elements completely', function(): void {
            $data = [
                'nested' => [
                    'items' => [
                        [
                            'id' => 1,
                            'tags' => ['a', 'b'],
                        ],
                        [
                            'id' => 2,
                            'tags' => ['c', 'd'],
                        ],
                    ],
                ],
            ];

            $result = DataMutator::make($data)->set('nested', [
                'items' => [
                    [
                        'tags' => ['x'],
                    ],
                    [
                        'tags' => ['y', 'z'],
                    ],
                ],
            ], true)->toArray();

            // Numeric indices are replaced completely, not merged
            expect($result)->toBe([
                'nested' => [
                    'items' => [
                        [
                            'tags' => ['x'],
                        ],
                        [
                            'tags' => ['y', 'z'],
                        ],
                    ],
                ],
            ]);
        });
    });

    describe('Merge shortcut method', function(): void {
        test('merge() is shortcut for set() with merge=true', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::make($data)->merge('user', [
                'age' => 30,
            ])->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ]);
        });

        test('merge() with single path and value', function(): void {
            $data = [];
            $result = DataMutator::make($data)->merge('user', [
                'name' => 'Alice',
            ])->toArray();
            $result = DataMutator::make($result)->merge('user', [
                'profile' => [
                    'city' => 'Berlin',
                ],
            ])->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ]);
        });

        test('merge() with array of paths', function(): void {
            $data = [
                'user' => [
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->merge([
                'user.profile.zip' => '10115', 'user.name' => 'Alice',
            ])->toArray();

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'city' => 'Berlin',
                        'zip' => '10115',
                    ],
                    'name' => 'Alice',
                ],
            ]);
        });

        test('merge() with numeric indices', function(): void {
            $data = [
                'tags' => ['old1', 'old2'],
            ];
            $result = DataMutator::make($data)->merge('tags', ['new1'])->toArray();

            expect($result)->toBe([
                'tags' => ['new1', 'old2'],
            ]);
        });

        test('merge() with objects', function(): void {
            $dto = new class {
                /** @var array<string, mixed> */
                public array $config = [
                    'debug' => true,
                ];
            };

            DataMutator::make($dto)->merge('config', [
                'cache' => 'redis',
            ]);

            expect($dto->config)->toBe([
                'debug' => true,
                'cache' => 'redis',
            ]);
        });
    });

    describe('Unset functionality', function(): void {
        test('can unset simple array values', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ];
            $result = DataMutator::make($data)->unset('user.age')->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('can unset nested array values', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('user.profile.city')->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'profile' => [],
                ],
            ]);
        });

        test('can unset multiple paths at once', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                    ],
                    [
                        'name' => 'Bob',
                        'age' => 25,
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset(['users.0.name', 'users.1.age'])->toArray();

            expect($result)->toBe([
                'users' => [
                    [],
                    [
                        'name' => 'Bob',
                    ],
                ],
            ]);
        });

        test('can unset from DTO objects', function(): void {
            $dto = new class {
                public string $name = 'Alice';
                public ?string $city = 'Berlin';
            };

            DataMutator::make($dto)->unset('city');

            expect($dto->name)->toBe('Alice');
            expect($dto->city)->toBeNull();
        });

        test('can unset private properties from objects', function(): void {
            $dto = new class {
                public string $name = 'Alice';

                /** @phpstan-ignore-next-line unknown */
                private ?string $secret = 'hidden';

                public function getSecret(): ?string
                {
                    return $this->secret;
                }
            };

            DataMutator::make($dto)->unset('secret');

            expect($dto->name)->toBe('Alice');
            expect($dto->getSecret())->toBeNull();
        });

        test('unset ignores non-existent paths', function(): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::make($data)->unset('user.nonexistent')->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('unset ignores non-existent object properties', function(): void {
            $dto = new class {
                public string $name = 'Alice';
            };

            DataMutator::make($dto)->unset('nonexistent');

            expect($dto->name)->toBe('Alice');
        });

        test('can unset with wildcards', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                        'age' => 30,
                    ],
                    [
                        'name' => 'Bob',
                        'age' => 25,
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('users.*.age')->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'name' => 'Alice',
                    ],
                    [
                        'name' => 'Bob',
                    ],
                ],
            ]);
        });

        test('can unset all items via wildcard', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                    ],
                    [
                        'name' => 'Bob',
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('users.*')->toArray();

            expect($result)->toBe([
                'users' => [],
            ]);
        });

        test('wildcard unset clears entire array', function(): void {
            $data = [
                'items' => ['a', 'b', 'c', 'd'],
            ];
            $result = DataMutator::make($data)->unset('items.*')->toArray();

            expect($result)->toBe([
                'items' => [],
            ]);
        });

        test('wildcard unset with nested arrays', function(): void {
            $data = [
                'categories' => [
                    'tech' => ['php', 'javascript'],
                    'design' => ['ui', 'ux'],
                ],
            ];
            $result = DataMutator::make($data)->unset('categories.*')->toArray();

            expect($result)->toBe([
                'categories' => [],
            ]);
        });

        test('wildcard unset preserves parent structure', function(): void {
            $data = [
                'config' => [
                    'database' => [
                        'host' => 'localhost',
                        'port' => 3306,
                    ],
                    'cache' => [
                        'driver' => 'redis',
                    ],
                ],
                'app' => [
                    'name' => 'MyApp',
                ],
            ];
            $result = DataMutator::make($data)->unset('config.*')->toArray();

            expect($result)->toBe([
                'config' => [],
                'app' => [
                    'name' => 'MyApp',
                ],
            ]);
        });

        test('wildcard unset with mixed object types', function(): void {
            $data = [
                'items' => [
                    [
                        'name' => 'Item1',
                        'price' => 100,
                    ],
                    'string_value',
                    [
                        'name' => 'Item2',
                        'price' => 200,
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('items.*.price')->toArray();

            expect($result)->toBe([
                'items' => [
                    [
                        'name' => 'Item1',
                    ],
                    'string_value', // string_value is unchanged (can't unset from string)
                    [
                        'name' => 'Item2',
                    ],
                ],
            ]);
        });

        test('wildcard unset clears mixed object types array', function(): void {
            $data = [
                'items' => [
                    [
                        'name' => 'Item1',
                        'price' => 100,
                    ],
                    'string_value',
                    [
                        'name' => 'Item2',
                        'price' => 200,
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('items.*')->toArray();

            expect($result)->toBe([
                'items' => [],
            ]);
        });

        test('wildcard unset with empty arrays', function(): void {
            $data = [
                'empty' => [],
                'filled' => ['a', 'b'],
            ];
            $result = DataMutator::make($data)->unset('empty.*')->toArray();

            expect($result)->toBe([
                'empty' => [],
                'filled' => ['a', 'b'],
            ]);
        });

        test('wildcard unset at root level', function(): void {
            $data = [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ];
            $result = DataMutator::make($data)->unset('*')->toArray();

            expect($result)->toBe([]);
        });

        test('can unset nested paths with wildcards', function(): void {
            $data = [
                'groups' => [
                    [
                        'users' => [
                            [
                                'name' => 'Alice',
                                'email' => 'alice@example.com',
                            ],
                        ],
                    ],
                    [
                        'users' => [
                            [
                                'name' => 'Bob',
                                'email' => 'bob@example.com',
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('groups.*.users.*.email')->toArray();

            expect($result)->toBe([
                'groups' => [
                    [
                        'users' => [
                            [
                                'name' => 'Alice',
                            ],
                        ],
                    ],
                    [
                        'users' => [
                            [
                                'name' => 'Bob',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('can unset from Arrayable objects', function(): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Alice',
                        'age' => 30,
                        'city' => 'Berlin',
                    ];
                }
            };

            $result = DataMutator::make($arrayable)->unset('age')->toArray();

            expect($result)->toBe([
                'name' => 'Alice',
                'city' => 'Berlin',
            ]);
        })->group('laravel');

        test('can unset from JsonSerializable objects', function(): void {
            $jsonSerializable = new class implements JsonSerializable {
                /** @return array<string, mixed> */
                public function jsonSerialize(): array
                {
                    return [
                        'name' => 'Alice',
                        'age' => 30,
                        'city' => 'Berlin',
                    ];
                }
            };

            $result = DataMutator::make($jsonSerializable)->unset('age')->toArray();

            expect($result)->toBe([
                'name' => 'Alice',
                'city' => 'Berlin',
            ]);
        });

        test('can unset nested values from objects in arrays', function(): void {
            $data = [
                'users' => [
                    new class {
                        public string $name = 'Alice';
                        public ?string $email = 'alice@example.com';
                    },
                    new class {
                        public string $name = 'Bob';
                        public ?string $email = 'bob@example.com';
                    },
                ],
            ];

            $result = DataMutator::make($data)->unset('users.*.email')->toArray();

            /** @var array{users: array<int, object>} $result */
            assert(is_array($result));

            /** @phpstan-ignore-next-line unknown */
            expect($result['users'][0]->name)->toBe('Alice');
            /** @phpstan-ignore-next-line unknown */
            expect($result['users'][0]->email)->toBeNull();
            /** @phpstan-ignore-next-line unknown */
            expect($result['users'][1]->name)->toBe('Bob');
            /** @phpstan-ignore-next-line unknown */
            expect($result['users'][1]->email)->toBeNull();
        });

        test('wildcard unset clears array of objects', function(): void {
            $data = [
                'users' => [
                    new class {
                        public string $name = 'Alice';
                    },
                    new class {
                        public string $name = 'Bob';
                    },
                ],
            ];

            $result = DataMutator::make($data)->unset('users.*')->toArray();

            expect($result)->toBe([
                'users' => [],
            ]);
        });

        test('wildcard unset with multiple levels', function(): void {
            $data = [
                'departments' => [
                    'engineering' => [
                        'teams' => [
                            [
                                'name' => 'Backend',
                                'lead' => 'Alice',
                            ],
                            [
                                'name' => 'Frontend',
                                'lead' => 'Bob',
                            ],
                        ],
                    ],
                    'design' => [
                        'teams' => [
                            [
                                'name' => 'UI',
                                'lead' => 'Charlie',
                            ],
                            [
                                'name' => 'UX',
                                'lead' => 'Diana',
                            ],
                        ],
                    ],
                ],
            ];

            $result = DataMutator::make($data)->unset('departments.*.teams.*.lead')->toArray();

            expect($result)->toBe([
                'departments' => [
                    'engineering' => [
                        'teams' => [
                            [
                                'name' => 'Backend',
                            ],
                            [
                                'name' => 'Frontend',
                            ],
                        ],
                    ],
                    'design' => [
                        'teams' => [
                            [
                                'name' => 'UI',
                            ],
                            [
                                'name' => 'UX',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('wildcard unset clears nested arrays completely', function(): void {
            $data = [
                'departments' => [
                    'engineering' => [
                        'teams' => [
                            [
                                'name' => 'Backend',
                            ],
                            [
                                'name' => 'Frontend',
                            ],
                        ],
                    ],
                    'design' => [
                        'teams' => [
                            [
                                'name' => 'UI',
                            ],
                            [
                                'name' => 'UX',
                            ],
                        ],
                    ],
                ],
            ];

            $result = DataMutator::make($data)->unset('departments.*.teams.*')->toArray();

            expect($result)->toBe([
                'departments' => [
                    'engineering' => [
                        'teams' => [],
                    ],
                    'design' => [
                        'teams' => [],
                    ],
                ],
            ]);
        });

        test('unset with deep object nesting', function(): void {
            $dto = new class {
                public object $profile;

                public function __construct()
                {
                    $this->profile = new class {
                        public string $name = 'Alice';
                        public ?string $city = 'Berlin';
                    };
                }
            };

            DataMutator::make($dto)->unset('profile.city');

            /** @phpstan-ignore-next-line unknown */
            expect($dto->profile->name)->toBe('Alice');
            /** @phpstan-ignore-next-line unknown */
            expect($dto->profile->city)->toBeNull();
        });

        test('can unset values from Collections', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                    'age' => 30,
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
            $result = DataMutator::make($collection)->unset('0.age')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
        })->group('laravel');

        test('can unset values with wildcards from Collections', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                    'age' => 30,
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
            $result = DataMutator::make($collection)->unset('*.name')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'age' => 30,
                ],
                [
                    'age' => 25,
                ],
            ]);
        })->group('laravel');

        test('can unset all items from Collection via wildcard', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
            $result = DataMutator::make($collection)->unset('*')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([]);
        })->group('laravel');

        test('can unset multiple paths from Collections', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                    'age' => 30,
                    'city' => 'Berlin',
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,

                    'city' => 'Munich',
                ],
            ]);
            $result = DataMutator::make($collection)->unset(['0.age', '1.city'])->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'name' => 'Alice',
                    'city' => 'Berlin',
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
        })->group('laravel');

        test('can unset nested values from Collections', function(): void {
            $collection = collect([
                [
                    'user' => [
                        'profile' => [
                            'name' => 'Alice',
                            'city' => 'Berlin',
                        ],
                    ],
                ],
                [
                    'user' => [
                        'profile' => [
                            'name' => 'Bob',
                            'city' => 'Munich',
                        ],
                    ],
                ],
            ]);
            $result = DataMutator::make($collection)->unset('*.user.profile.city')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'user' => [
                        'profile' => [
                            'name' => 'Alice',
                        ],
                    ],
                ],
                [
                    'user' => [
                        'profile' => [
                            'name' => 'Bob',
                        ],
                    ],
                ],
            ]);
        })->group('laravel');

        test('can unset from empty Collections', function(): void {
            $collection = collect([]);
            $result = DataMutator::make($collection)->unset('*')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([]);
        })->group('laravel');

        test('can unset from Collections with mixed types', function(): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                    'type' => 'user',
                ],
                'string_value',
                [
                    'name' => 'Bob',
                    'type' => 'admin',
                ],
            ]);
            $result = DataMutator::make($collection)->unset('*.type')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'name' => 'Alice',
                ],
                'string_value', // unchanged
                [
                    'name' => 'Bob',
                ],
            ]);
        })->group('laravel');
    });

    describe('Deep Wildcards', function(): void {
        test('can set values with deep wildcards in arrays', function(): void {
            $data = [
                'users' => [
                    [
                        'profile' => [
                            [
                                'city' => 'Berlin',
                            ],
                            [
                                'city' => 'Munich',
                            ],
                        ],
                    ],
                    [
                        'profile' => [
                            [
                                'city' => 'Hamburg',
                            ],
                            [
                                'city' => 'Cologne',
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('users.*.profile.*.country', 'Germany')->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'profile' => [
                            [
                                'city' => 'Berlin',
                                'country' => 'Germany',
                            ],
                            [
                                'city' => 'Munich',
                                'country' => 'Germany',
                            ],
                        ],
                    ],
                    [
                        'profile' => [
                            [
                                'city' => 'Hamburg',
                                'country' => 'Germany',
                            ],
                            [
                                'city' => 'Cologne',
                                'country' => 'Germany',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('can unset values with deep wildcards in arrays', function(): void {
            $data = [
                'users' => [
                    [
                        'profile' => [
                            [
                                'city' => 'Berlin',
                                'country' => 'Germany',
                            ],
                            [
                                'city' => 'Munich',
                                'country' => 'Germany',
                            ],
                        ],
                    ],
                    [
                        'profile' => [
                            [
                                'city' => 'Hamburg',
                                'country' => 'Germany',
                            ],
                            [
                                'city' => 'Cologne',
                                'country' => 'Germany',
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('users.*.profile.*.city')->toArray();

            expect($result)->toBe([
                'users' => [
                    [
                        'profile' => [
                            [
                                'country' => 'Germany',
                            ],
                            [
                                'country' => 'Germany',
                            ],
                        ],
                    ],
                    [
                        'profile' => [
                            [
                                'country' => 'Germany',
                            ],
                            [
                                'country' => 'Germany',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('can merge values with deep wildcards', function(): void {
            $data = [
                'departments' => [
                    [
                        'teams' => [
                            [
                                'members' => ['Alice'],
                            ],
                            [
                                'members' => ['Bob'],
                            ],
                        ],
                    ],
                    [
                        'teams' => [
                            [
                                'members' => ['Charlie'],
                            ],
                            [
                                'members' => ['Diana'],
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->merge('departments.*.teams.*.members', ['NewMember'])->toArray();

            expect($result)->toBe([
                'departments' => [
                    [
                        'teams' => [
                            [
                                'members' => ['NewMember'],
                            ],
                            [
                                'members' => ['NewMember'],
                            ],
                        ],
                    ],
                    [
                        'teams' => [
                            [
                                'members' => ['NewMember'],
                            ],
                            [
                                'members' => ['NewMember'],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('deep wildcards with three levels', function(): void {
            $data = [
                'companies' => [
                    'departments' => [
                        [
                            'teams' => [
                                [
                                    'projects' => [
                                        [
                                            'name' => 'Project A',
                                        ],
                                        [
                                            'name' => 'Project B',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'teams' => [
                                [
                                    'projects' => [
                                        [
                                            'name' => 'Project C',
                                        ],
                                        [
                                            'name' => 'Project D',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set(
                'companies.departments.*.teams.*.projects.*.status',
                'active'
            )->toArray();

            expect($result)->toBe([
                'companies' => [
                    'departments' => [
                        [
                            'teams' => [
                                [
                                    'projects' => [
                                        [
                                            'name' => 'Project A',
                                            'status' => 'active',
                                        ],
                                        [
                                            'name' => 'Project B',
                                            'status' => 'active',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'teams' => [
                                [
                                    'projects' => [
                                        [
                                            'name' => 'Project C',
                                            'status' => 'active',
                                        ],
                                        [
                                            'name' => 'Project D',
                                            'status' => 'active',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('deep wildcards with mixed array and object structures', function(): void {
            $data = [
                'organizations' => [
                    new class {
                        /** @var array<int, array{locations: array<int, array<string, string>>}> */
                        public array $divisions = [
                            [
                                'locations' => [
                                    [
                                        'address' => 'Street 1',
                                    ],
                                    [
                                        'address' => 'Street 2',
                                    ],
                                ],
                            ],
                            [
                                'locations' => [
                                    [
                                        'address' => 'Street 3',
                                    ],
                                    [
                                        'address' => 'Street 4',
                                    ],
                                ],
                            ],
                        ];
                    },
                    new class {
                        /** @var array<int, array{locations: array<int, array<string, string>>}> */
                        public array $divisions = [
                            [
                                'locations' => [
                                    [
                                        'address' => 'Avenue 1',
                                    ],
                                    [
                                        'address' => 'Avenue 2',
                                    ],
                                ],
                            ],
                        ];
                    },
                ],
            ];
            $result = DataMutator::make($data)->set(
                'organizations.*.divisions.*.locations.*.country',
                'USA'
            )->toArray();

            /** @var array{organizations: array<int, object>} $result */
            assert(is_array($result));

            /** @phpstan-ignore-next-line unknown */
            expect($result['organizations'][0]->divisions[0]['locations'][0]['country'])->toBe('USA');
            /** @phpstan-ignore-next-line unknown */
            expect($result['organizations'][0]->divisions[0]['locations'][1]['country'])->toBe('USA');
            /** @phpstan-ignore-next-line unknown */
            expect($result['organizations'][0]->divisions[1]['locations'][0]['country'])->toBe('USA');
            /** @phpstan-ignore-next-line unknown */
            expect($result['organizations'][1]->divisions[0]['locations'][0]['country'])->toBe('USA');
        });

        test('deep wildcards unset clears nested arrays completely', function(): void {
            $data = [
                'regions' => [
                    [
                        'countries' => [
                            [
                                'cities' => ['Berlin', 'Munich'],
                            ],
                            [
                                'cities' => ['Hamburg', 'Cologne'],
                            ],
                        ],
                    ],
                    [
                        'countries' => [
                            [
                                'cities' => ['Vienna', 'Salzburg'],
                            ],
                            [
                                'cities' => ['Graz', 'Linz'],
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->unset('regions.*.countries.*.cities.*')->toArray();

            expect($result)->toBe([
                'regions' => [
                    [
                        'countries' => [
                            [
                                'cities' => [],
                            ],
                            [
                                'cities' => [],
                            ],
                        ],
                    ],
                    [
                        'countries' => [
                            [
                                'cities' => [],
                            ],
                            [
                                'cities' => [],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('deep wildcards with Collections', function(): void {
            $collection = collect([
                [
                    'groups' => [
                        [
                            'items' => [
                                [
                                    'value' => 1,
                                ],
                                [
                                    'value' => 2,
                                ],
                            ],
                        ],
                        [
                            'items' => [
                                [
                                    'value' => 3,
                                ],
                                [
                                    'value' => 4,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'groups' => [
                        [
                            'items' => [
                                [
                                    'value' => 5,
                                ],
                                [
                                    'value' => 6,
                                ],
                            ],
                        ],
                        [
                            'items' => [
                                [
                                    'value' => 7,
                                ],
                                [
                                    'value' => 8,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
            $result = DataMutator::make($collection)->set('*.groups.*.items.*.category', 'processed')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'groups' => [
                        [
                            'items' => [
                                [
                                    'value' => 1,
                                    'category' => 'processed',
                                ],
                                [
                                    'value' => 2,
                                    'category' => 'processed',
                                ],
                            ],
                        ],
                        [
                            'items' => [
                                [
                                    'value' => 3,
                                    'category' => 'processed',
                                ],
                                [
                                    'value' => 4,
                                    'category' => 'processed',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'groups' => [
                        [
                            'items' => [
                                [
                                    'value' => 5,
                                    'category' => 'processed',
                                ],
                                [
                                    'value' => 6,
                                    'category' => 'processed',
                                ],
                            ],
                        ],
                        [
                            'items' => [
                                [
                                    'value' => 7,
                                    'category' => 'processed',
                                ],
                                [
                                    'value' => 8,
                                    'category' => 'processed',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        })->group('laravel');

        test('deep wildcards with empty nested arrays', function(): void {
            $data = [
                'levels' => [
                    [
                        'sublevel' => [],
                    ],
                    [
                        'sublevel' => [
                            [
                                'items' => [
                                    [
                                        'name' => 'test',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'sublevel' => [],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('levels.*.sublevel.*.items.*.status', 'active')->toArray();

            expect($result)->toBe([
                'levels' => [
                    [
                        'sublevel' => [],
                    ],
                    [
                        'sublevel' => [
                            [
                                'items' => [
                                    [
                                        'name' => 'test',
                                        'status' => 'active',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'sublevel' => [],
                    ],
                ],
            ]);
        });

        test('deep wildcards with non-array elements are ignored', function(): void {
            $data = [
                'mixed' => [
                    [
                        'nested' => [
                            [
                                'value' => 1,
                            ],
                            'string_value',
                            [
                                'value' => 2,
                            ],
                        ],
                    ],
                    [
                        'nested' => 'not_an_array',
                    ],
                    [
                        'nested' => [
                            [
                                'value' => 3,
                            ],
                            [
                                'value' => 4,
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set('mixed.*.nested.*.category', 'tagged')->toArray();

            expect($result)->toBe([
                'mixed' => [
                    [
                        'nested' => [
                            [
                                'value' => 1,
                                'category' => 'tagged',
                            ],
                            'string_value',
                            [
                                'value' => 2,
                                'category' => 'tagged',
                            ],
                        ],
                    ],
                    [
                        'nested' => [],
                    ], // converted to empty array when wildcard is applied
                    [
                        'nested' => [
                            [
                                'value' => 3,
                                'category' => 'tagged',
                            ],
                            [
                                'value' => 4,
                                'category' => 'tagged',
                            ],
                        ],
                    ],
                ],
            ]);
        });

        test('deep wildcards unset from Collections', function(): void {
            $collection = collect([
                [
                    'users' => [
                        [
                            'profile' => [
                                [
                                    'city' => 'Berlin',
                                ],
                                [
                                    'city' => 'Munich',
                                ],
                            ],
                        ],
                        [
                            'profile' => [
                                [
                                    'city' => 'Hamburg',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'users' => [
                        [
                            'profile' => [
                                [
                                    'city' => 'Vienna',
                                ],
                                [
                                    'city' => 'Salzburg',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
            $result = DataMutator::make($collection)->unset('*.users.*.profile.*.city')->toArray();

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'users' => [
                        [
                            'profile' => [[], []],
                        ],
                        [
                            'profile' => [[]],
                        ],
                    ],
                ],
                [
                    'users' => [
                        [
                            'profile' => [[], []],
                        ],
                    ],
                ],
            ]);
        })->group('laravel');

        test('deep wildcards with multiple values assignment', function(): void {
            $data = [
                'stores' => [
                    [
                        'products' => [
                            [
                                'info' => [
                                    'name' => 'Product A',
                                ],
                            ],
                            [
                                'info' => [
                                    'name' => 'Product B',
                                ],
                            ],
                        ],
                    ],
                    [
                        'products' => [
                            [
                                'info' => [
                                    'name' => 'Product C',
                                ],
                            ],
                            [
                                'info' => [
                                    'name' => 'Product D',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $result = DataMutator::make($data)->set([
                'stores.*.products.*.info.price' => 99.99, 'stores.*.products.*.info.currency' => 'EUR',
            ])->toArray();

            expect($result)->toBe([
                'stores' => [
                    [
                        'products' => [
                            [
                                'info' => [
                                    'name' => 'Product A',
                                    'price' => 99.99,
                                    'currency' => 'EUR',
                                ],
                            ],
                            [
                                'info' => [
                                    'name' => 'Product B',
                                    'price' => 99.99,
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                    ],
                    [
                        'products' => [
                            [
                                'info' => [
                                    'name' => 'Product C',
                                    'price' => 99.99,
                                    'currency' => 'EUR',
                                ],
                            ],
                            [
                                'info' => [
                                    'name' => 'Product D',
                                    'price' => 99.99,
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        });
    });

    describe('Error handling', function(): void {
        test('throws TypeError for unsupported target types', function(): void {
            /** @phpstan-ignore-next-line unknown */
            $string = 'string';
            expect(fn(): DataMutator => DataMutator::make($string)->set('path', 'value'))
                ->toThrow(TypeError::class);

            /** @phpstan-ignore-next-line unknown */
            $int = 42;
            expect(fn(): DataMutator => DataMutator::make($int)->set('path', 'value'))
                ->toThrow(TypeError::class);

            /** @phpstan-ignore-next-line unknown */
            $bool = true;
            expect(fn(): DataMutator => DataMutator::make($bool)->set('path', 'value'))
                ->toThrow(TypeError::class);
        });

        test('unset throws TypeError for unsupported target types', function(): void {
            /** @phpstan-ignore-next-line unknown */
            $string = 'string';
            expect(fn(): DataMutator => DataMutator::make($string)->unset('path'))
                ->toThrow(TypeError::class);
        });
    });
});
