<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMutator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

describe('DataMutator', function (): void {
    describe('Array mutations - Single values', function (): void {
        test('can set simple values in empty array', function (): void {
            $data = [];
            $result = DataMutator::set($data, 'name', 'Alice');

            expect($result)->toBe([
                'name' => 'Alice',
            ]);
        });

        test('can set nested values in empty array', function (): void {
            $data = [];
            $result = DataMutator::set($data, 'user.profile.name', 'Alice');

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                    ],
                ],
            ]);
        });

        test('can set values in existing array', function (): void {
            $data = [
                'existing' => 'value',
            ];
            $result = DataMutator::set($data, 'name', 'Alice');

            expect($result)->toBe([
                'existing' => 'value',
                'name' => 'Alice',
            ]);
        });

        test('can overwrite existing values', function (): void {
            $data = [
                'name' => 'Bob',
            ];
            $result = DataMutator::set($data, 'name', 'Alice');

            expect($result)->toBe([
                'name' => 'Alice',
            ]);
        });

        test('can set deeply nested values', function (): void {
            $data = [];
            $result = DataMutator::set($data, 'level1.level2.level3.level4.value', 'deep');

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

        test('can overwrite scalar with nested structure', function (): void {
            $data = [
                'user' => 'scalar_value',
            ];
            $result = DataMutator::set($data, 'user.profile.name', 'Alice');

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                    ],
                ],
            ]);
        });
    });

    describe('Array mutations - Multiple values', function (): void {
        test('can set multiple values with array of paths', function (): void {
            $data = [];
            $result = DataMutator::set($data, [
                'users.0.name' => 'Alice',
                'users.1.name' => 'Bob',
                'users.1.age' => 25,
            ]);

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

        test('can set multiple nested values', function (): void {
            $data = [];
            $result = DataMutator::set($data, [
                'config.database.host' => 'localhost',
                'config.database.port' => 3306,
                'config.cache.driver' => 'redis',
                'config.cache.ttl' => 3600,
            ]);

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

        test('can overwrite existing values with multiple paths', function (): void {
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
            $result = DataMutator::set($data, [
                'users.0.name' => 'NewAlice',
                'users.0.email' => 'alice@example.com',
                'users.1.age' => 30,
            ]);

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

        test('can mix simple and nested paths in multiple assignment', function (): void {
            $data = [];
            $result = DataMutator::set($data, [
                'name' => 'Alice',
                'profile.age' => 30,
                'profile.address.city' => 'Berlin',
                'profile.address.country' => 'Germany',
                'settings.theme' => 'dark',
            ]);

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

        test('can set empty array with multiple paths', function (): void {
            $data = [];
            $result = DataMutator::set($data, []);

            expect($result)->toBe([]);
        });

        test('can set multiple values with special types', function (): void {
            $data = [];
            $result = DataMutator::set($data, [
                'string' => 'text',
                'number' => 42,
                'float' => 19.99,
                'boolean' => true,
                'null' => null,
                'array' => ['a', 'b', 'c'],
            ]);

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

    describe('Array mutations - Merge functionality', function (): void {
        test('can merge arrays without merge flag (overwrites)', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::set($data, 'user', [
                'age' => 30,
            ]);

            expect($result)->toBe([
                'user' => [
                    'age' => 30,
                ],
            ]);
        });

        test('can merge arrays with merge flag', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::set($data, 'user', [
                'age' => 30,
            ], merge: true);

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ]);
        });

        test('can perform deep merge', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ];
            $result = DataMutator::set($data, 'user', [
                'age' => 30,
                'profile' => [
                    'zip' => '10115',
                ],
            ], merge: true);

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

        test('can merge multiple levels deep', function (): void {
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
            $result = DataMutator::set($data, 'config', [
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
            ], merge: true);

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

        test('merge overwrites scalar values', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'age' => 25,
                ],
            ];
            $result = DataMutator::set($data, 'user', [
                'age' => 30,
            ], merge: true);

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30, // overwritten
                ],
            ]);
        });

        test('merge with non-array values falls back to overwrite', function (): void {
            $data = [
                'user' => 'Alice',
            ];
            $result = DataMutator::set($data, 'user', [
                'age' => 30,
            ], merge: true);

            expect($result)->toBe([
                'user' => [
                    'age' => 30,
                ],
            ]);
        });

        test('merge with non-array new value falls back to overwrite', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::set($data, 'user', 'Bob', merge: true);

            expect($result)->toBe([
                'user' => 'Bob',
            ]);
        });

        test('can merge empty arrays', function (): void {
            $data = [
                'user' => [],
            ];
            $result = DataMutator::set($data, 'user', [
                'name' => 'Alice',
            ], merge: true);

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('can merge into empty target', function (): void {
            $data = [];
            $result = DataMutator::set($data, 'user', [
                'name' => 'Alice',
            ], merge: true);

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('numeric indices are replaced, not merged', function (): void {
            $data = [
                'tags' => ['old', 'keep'],
            ];
            $result = DataMutator::set($data, 'tags', ['new'], merge: true);

            expect($result)->toBe([
                'tags' => ['new', 'keep'],
            ]);
        });

        test('numeric indices replacement with multiple values', function (): void {
            $data = [
                'items' => ['a', 'b', 'c', 'd'],
            ];
            $result = DataMutator::set($data, 'items', ['x', 'y'], merge: true);

            expect($result)->toBe([
                'items' => ['x', 'y', 'c', 'd'],
            ]);
        });

        test('numeric indices replacement preserves higher indices', function (): void {
            $data = [
                'list' => [
                    0 => 'zero',
                    1 => 'one',
                    2 => 'two',
                    5 => 'five',
                ],
            ];
            $result = DataMutator::set($data, 'list', [
                0 => 'NEW_ZERO',
                2 => 'NEW_TWO',
            ], merge: true);

            expect($result)->toBe([
                'list' => [
                    0 => 'NEW_ZERO',
                    1 => 'one',
                    2 => 'NEW_TWO',
                    5 => 'five',
                ],
            ]);
        });

        test('mixed associative and numeric keys merge correctly', function (): void {
            $data = [
                'config' => [
                    'name' => 'app',
                    'tags' => ['old1', 'old2'],
                    'version' => '1.0',
                ],
            ];
            $result = DataMutator::set($data, 'config', [
                'tags' => ['new1'],
                'description' => 'My App',
            ], merge: true);

            expect($result)->toBe([
                'config' => [
                    'name' => 'app',
                    'tags' => ['new1', 'old2'],
                    'version' => '1.0',
                    'description' => 'My App',
                ],
            ]);
        });

        test('deep merge with numeric indices in nested arrays', function (): void {
            $data = [
                'users' => [
                    'alice' => [
                        'permissions' => ['read', 'write'],
                        'roles' => ['user'],
                    ],
                ],
            ];
            $result = DataMutator::set($data, 'users', [
                'alice' => [
                    'permissions' => ['admin'],
                    'email' => 'alice@example.com',
                ],
            ], merge: true);

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

        test('empty numeric array merge', function (): void {
            $data = [
                'items' => [],
            ];
            $result = DataMutator::set($data, 'items', ['first'], merge: true);

            expect($result)->toBe([
                'items' => ['first'],
            ]);
        });

        test('numeric merge with non-sequential indices', function (): void {
            $data = [
                'sparse' => [
                    0 => 'zero',
                    3 => 'three',
                    7 => 'seven',
                ],
            ];
            $result = DataMutator::set($data, 'sparse', [
                1 => 'one',
                3 => 'NEW_THREE',
            ], merge: true);

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

    describe('Array mutations with wildcards', function (): void {
        test('can set values with single wildcard', function (): void {
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
            $result = DataMutator::set($data, 'users.*.name', 'Bob');

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

        test('can set values with wildcard at root level', function (): void {
            $data = ['', '', ''];
            $result = DataMutator::set($data, '*', 'value');

            expect($result)->toBe(['value', 'value', 'value']);
        });

        test('can set nested values with wildcards', function (): void {
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
            $result = DataMutator::set($data, 'orders.*.items.*.price', 100);

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

        test('wildcard on empty array does nothing', function (): void {
            $data = [
                'users' => [],
            ];
            $result = DataMutator::set($data, 'users.*.name', 'Bob');

            expect($result)->toBe([
                'users' => [],
            ]);
        });

        test('wildcard on non-array elements is ignored', function (): void {
            $data = [
                'items' => [
                    'string_value',
                    [
                        'name' => 'object',
                    ],
                ],
            ];
            $result = DataMutator::set($data, 'items.*.name', 'test');

            expect($result)->toBe([
                'items' => [
                    'string_value', // unchanged
                    [
                        'name' => 'test',
                    ], // changed
                ],
            ]);
        });

        test('can merge arrays with wildcards', function (): void {
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
            $result = DataMutator::set($data, 'users.*', [
                'profile' => [
                    'country' => 'Germany',
                ],
            ], merge: true);

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

        test('wildcard merge with non-array elements falls back to overwrite', function (): void {
            $data = [
                'items' => [
                    'string_value',
                    [
                        'name' => 'object',
                    ],
                ],
            ];
            $result = DataMutator::set($data, 'items.*', [
                'new' => 'value',
            ], merge: true);

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

        test('wildcard merge with numeric indices', function (): void {
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
            $result = DataMutator::set($data, 'groups.*', [
                'tags' => ['new1'],
                'active' => true,
            ], merge: true);

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

    describe('Object mutations - DTOs', function (): void {
        test('can set public properties on DTO', function (): void {
            $dto = new class {
                public string $name = '';
                public int $age = 0;
            };

            DataMutator::set($dto, 'name', 'Charlie');

            /** @var object $result */
            expect($dto->name)->toBe('Charlie');
            expect($dto->age)->toBe(0); // unchanged
        });

        test('can set multiple properties on DTO', function (): void {
            $dto = new class {
                public string $name = '';
                public int $age = 0;
                public string $email = '';

                /** @var array<string, mixed> */
                public array $config = [];
            };
            DataMutator::set($dto, [
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',

                'config.theme' => 'dark',
                'config.language' => 'en',
            ]);

            /** @var object $result */
            expect($dto->name)->toBe('Alice');
            expect($dto->age)->toBe(30);
            expect($dto->email)->toBe('alice@example.com');
            expect($dto->config)->toBe([
                'theme' => 'dark',
                'language' => 'en',
            ]);
        });

        test('can set private properties on DTO using reflection', function (): void {
            $dto = new class {
                private string $name = '';

                public function getName(): string
                {
                    return $this->name;
                }
            };

            DataMutator::set($dto, 'name', 'Charlie');

            /** @var object $result */
            expect($dto->getName())->toBe('Charlie');
        });

        test('can create dynamic properties on DTO', function (): void {
            $dto = new #[\AllowDynamicProperties] class {
            };

            DataMutator::set($dto, 'dynamicProperty', 'value');

            // @phpstan-ignore-next-line intentional dynamic property created by mutator in test
            expect($dto->dynamicProperty)->toBe('value');
        });

        test('can set nested values in DTO properties', function (): void {
            $dto = new class {
                /** @var array<string, mixed> */
                public array $config = [];
            };

            DataMutator::set($dto, 'config.database.host', 'localhost');

            expect($dto->config)->toBe([
                'database' => [
                    'host' => 'localhost',
                ],
            ]);
        });

        test('can merge arrays in DTO properties', function (): void {
            $dto = new class {
                /** @var array<string, mixed> */
                public array $config = [
                    'database' => [
                        'host' => 'localhost',
                    ],
                ];
            };

            DataMutator::set($dto, 'config', [
                'database' => [
                    'port' => 3306,
                ],
                'cache' => [
                    'driver' => 'redis',
                ],
            ], merge: true);

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

    describe('Laravel Model mutations', function (): void {
        test('can set attributes on Laravel model', function (): void {
            $model = new class extends Model {
                protected $fillable = ['name', 'email'];
            };

            DataMutator::set($model, 'name', 'Alice');

            expect($model->getAttribute('name'))->toBe('Alice');
        });

        test('can set multiple attributes on Laravel model', function (): void {
            $model = new class extends Model {
                protected $fillable = ['name', 'email'];
            };

            $result1 = DataMutator::set($model, 'name', 'Alice');
            $result2 = DataMutator::set($result1, 'email', 'alice@example.com');

            expect($model->getAttribute('name'))->toBe('Alice');
            expect($model->getAttribute('email'))->toBe('alice@example.com');
        });

        test('can set multiple attributes at once on Laravel model', function (): void {
            $model = new class extends Model {
                protected $fillable = ['name', 'email', 'age', 'active'];
            };

            DataMutator::set($model, [
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'age' => 25,
                'active' => true,
            ]);

            expect($model->getAttribute('name'))->toBe('Bob');
            expect($model->getAttribute('email'))->toBe('bob@example.com');
            expect($model->getAttribute('age'))->toBe(25);
            expect($model->getAttribute('active'))->toBe(true);
        });
    });

    describe('Arrayable object mutations', function (): void {
        test('converts Arrayable to array and sets values', function (): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Bob',
                        'age' => 25,
                    ];
                }
            };

            $result = DataMutator::set($arrayable, 'name', 'Alice');

            expect($result)->toBeArray();
            expect($result)->toBe([
                'name' => 'Alice',
                'age' => 25,
            ]);
        });

        test('can set nested values in Arrayable', function (): void {
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

            $result = DataMutator::set($arrayable, 'user.profile.age', 30);

            expect($result)->toBe([
                'user' => [
                    'profile' => [
                        'name' => 'Bob',
                        'age' => 30,
                    ],
                ],
            ]);
        });

        test('can set multiple values in Arrayable', function (): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Bob',
                        'age' => 25,
                    ];
                }
            };

            $result = DataMutator::set($arrayable, [
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
                'profile.city' => 'Berlin',
            ]);

            expect($result)->toBe([
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
                'profile' => [
                    'city' => 'Berlin',
                ],
            ]);
        });

        test('can merge arrays in Arrayable objects', function (): void {
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

            $result = DataMutator::set($arrayable, 'profile', [
                'country' => 'Germany',
            ], merge: true);

            expect($result)->toBe([
                'name' => 'Bob',
                'profile' => [
                    'city' => 'Munich',
                    'country' => 'Germany',
                ],
            ]);
            /** @return array<string, mixed> */
        });
    });

    describe('JsonSerializable object mutations', function (): void {
        test('converts JsonSerializable to array and sets values', function (): void {
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

            $result = DataMutator::set($jsonSerializable, 'name', 'Alice');

            /** @return array<string, mixed> */
            expect($result)->toBeArray();
            expect($result)->toBe([
                'name' => 'Alice',
                'age' => 25,
            ]);
        });

        test('can set multiple values in JsonSerializable', function (): void {
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

            $result = DataMutator::set($jsonSerializable, [
                'name' => 'Charlie',
                'age' => 35,
                'location.city' => 'Munich',
                'location.country' => 'Germany',
                /** @return array<string, mixed> */
            ]);

            expect($result)->toBe([
                'name' => 'Charlie',
                'age' => 35,
                'location' => [
                    'city' => 'Munich',
                    'country' => 'Germany',
                ],
            ]);
        });

        test('can merge arrays in JsonSerializable objects', function (): void {
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

            $result = DataMutator::set($jsonSerializable, 'settings', [
                'language' => 'en',
            ], merge: true);

            expect($result)->toBe([
                'name' => 'David',
                'settings' => [
                    'theme' => 'dark',
                    'language' => 'en',
                ],
            ]);
        });
    });

    describe('Collection mutations', function (): void {
        test('can set values in Collections', function (): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
            $result = DataMutator::set($collection, '0.age', 30);

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

        test('can set values with wildcards in Collections', function (): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
            $result = DataMutator::set($collection, '*.age', 25);
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

        test('can merge values in Collections', function (): void {
            $collection = collect([
                [
                    'config' => [
                        'debug' => true,
                    ],
                ],
            ]);
            $result = DataMutator::merge($collection, '0.config', [
                'cache' => 'redis',
            ]);

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

        test('can set multiple values in Collections', function (): void {
            $collection = collect([[], []]);
            $result = DataMutator::set($collection, [
                '0.name' => 'Alice',
                '1.name' => 'Bob',
                '1.age' => 25,
            ]);

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

        test('can merge with numeric indices in Collections', function (): void {
            $collection = collect([
                [
                    'tags' => ['old1', 'old2'],
                ],
            ]);
            $result = DataMutator::merge($collection, '0.tags', ['new1']);

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([
                [
                    'tags' => ['new1', 'old2'],
                ],
            ]);
        });
    });

    describe('Special value types', function (): void {
        test('can set null values', function (): void {
            $data = [];
            $result = DataMutator::set($data, 'value', null);

            expect($result)->toBe([
                'value' => null,
            ]);
        });

        test('can set boolean values', function (): void {
            $data = [];
            $result1 = DataMutator::set($data, 'enabled', true);
            $result2 = DataMutator::set($result1, 'disabled', false);

            expect($result2)->toBe([
                'enabled' => true,
                'disabled' => false,
            ]);
        });

        test('can set numeric values', function (): void {
            $data = [];
            $result1 = DataMutator::set($data, 'count', 42);
            $result2 = DataMutator::set($result1, 'price', 19.99);

            expect($result2)->toBe([
                'count' => 42,
                'price' => 19.99,
            ]);
        });

        test('can set array values', function (): void {
            $data = [];
            $result = DataMutator::set($data, 'items', ['a', 'b', 'c']);

            expect($result)->toBe([
                'items' => ['a', 'b', 'c'],
            ]);
        });

        test('can set object values', function (): void {
            $data = [];
            $obj = new stdClass();
            $obj->prop = 'value';

            $result = DataMutator::set($data, 'object', $obj);

            /** @var array<string, mixed> $result */
            assert(is_array($result));
            expect($result['object'])->toBe($obj);
        });
    });

    describe('Edge cases', function (): void {
        test('handles empty path segments gracefully', function (): void {
            $data = [];
            $result = DataMutator::set($data, '', 'value');

            // Empty path is ignored by DotPathHelper (segments('') => []); result remains unchanged
            expect($result)->toBe([]);
        });

        test('handles paths with consecutive dots', function (): void {
            $data = [];

            expect(fn(): array|object => DataMutator::set($data, 'a..b', 'value'))
                ->toThrow(InvalidArgumentException::class, "Invalid dot-path syntax: double dot in 'a..b'");
        });

        test('can set values in mixed object-array structures', function (): void {
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

            DataMutator::set($dto, 'items.0.price', 100);

            expect($dto->items[0])->toBe([
                'name' => 'item1',
                'price' => 100,
            ]);
            expect($dto->items[1])->toBe([
                'name' => 'item2',
            ]);
        });

        test('can handle null properties in objects', function (): void {
            $dto = new class {
                /** @var null|array<string, mixed> */
                public ?array $config = null;
            };

            DataMutator::set($dto, 'config.database.host', 'localhost');

            expect($dto->config)->toBe([
                'database' => [
                    'host' => 'localhost',
                ],
            ]);
        });

        test('wildcard with mixed object types in array', function (): void {
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

            $result = DataMutator::set($data, 'items.*.name', 'updated');

            /** @var array{items: array<int, mixed>} $result */
            assert(is_array($result));

            /** @var object $o0 */
            $o0 = $result['items'][0];

            /** @var array<string, mixed> $a1 */
            $a1 = $result['items'][1];

            /** @var object $o2 */
            $o2 = $result['items'][2];

            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($o0->name)->toBe('updated');
            expect($a1['name'])->toBe('updated');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($o2->name)->toBe('updated');
        });

        test('can combine wildcards with multiple value assignment', function (): void {
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

            $result = DataMutator::set($data, [
                'users.*.name' => 'DefaultUser',
                'users.*.active' => true,
                'config.theme' => 'dark',
                'config.language' => 'en',
            ]);

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

        test('multiple values with overlapping paths', function (): void {
            $data = [];

            $result = DataMutator::set($data, [
                'user.profile.name' => 'Alice',
                'user.profile.age' => 30,
                'user.settings.theme' => 'dark',
                'user.permissions.admin' => true,
            ]);

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

        test('multiple values can overwrite each other', function (): void {
            $data = [];

            $result = DataMutator::set($data, [
                'value' => 'second', // This should overwrite the first
                // @phpstan-ignore-next-line duplicate key intentional for overwrite behavior
                'nested.value' => 'nested_first',
                // @phpstan-ignore-next-line duplicate key intentional for overwrite behavior
                'nested.value' => 'nested_second', // This should overwrite too
            ]);

            expect($result)->toBe([
                'value' => 'second',
                'nested' => [
                    'value' => 'nested_second',
                ],
            ]);
        });

        test('can use merge with multiple values', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
                'config' => [
                    'theme' => 'light',
                ],
            ];

            $result = DataMutator::set($data, [
                'user' => [
                    'age' => 30,
                ],
                'config' => [
                    'language' => 'en',
                ],
            ], merge: true);

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

        test('merge with multiple values and deep structures', function (): void {
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

            $result = DataMutator::set($data, [
                'app' => [
                    'database' => [
                        'port' => 3306,
                    ],
                    'mail' => [
                        'driver' => 'smtp',
                    ],
                ],
            ], merge: true);

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

        test('multiple values merge with numeric indices', function (): void {
            $data = [
                'lists' => [
                    'tags' => ['old1', 'old2'],
                    'categories' => ['cat1', 'cat2', 'cat3'],
                ],
            ];

            $result = DataMutator::set($data, [
                'lists' => [
                    'tags' => ['new1'],
                    'categories' => ['newcat1', 'newcat2'],
                ],
            ], merge: true);

            expect($result)->toBe([
                'lists' => [
                    'tags' => ['new1', 'old2'],
                    'categories' => ['newcat1', 'newcat2', 'cat3'],
                ],
            ]);
        });

        test('complex merge with mixed associative and numeric keys', function (): void {
            $data = [
                'system' => [
                    'modules' => ['auth', 'cache'],
                    'config' => [
                        'debug' => true,
                        'features' => ['feature1', 'feature2'],
                    ],
                ],
            ];

            $result = DataMutator::set($data, [
                'system' => [
                    'modules' => ['logging'],
                    'config' => [
                        'features' => ['newfeature'],
                        'version' => '2.0',
                    ],
                ],
            ], merge: true);

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

        test('numeric merge with string keys that look like numbers', function (): void {
            $data = [
                'mixed' => [
                    // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                    '0' => 'string_zero',
                    // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                    0 => 'int_zero',
                    // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                    '1' => 'string_one',
                    // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test

                    // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                    1 => 'int_one',
                    // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                ],
                // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
            ];

            $result = DataMutator::set($data, 'mixed', [
                // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                '0' => 'new_string_zero',
                // @phpstan-ignore-next-line duplicate numeric/string keys intentional for test
                0 => 'new_int_zero',
            ], merge: true);
            // @phpstan-ignore-next-line duplicate numeric keys intentional for expected array literal

            expect($result)->toBe([
                'mixed' => [
                    // @phpstan-ignore-next-line duplicate numeric keys intentional for expected array literal
                    '0' => 'new_string_zero',

                    // @phpstan-ignore-next-line duplicate numeric keys intentional for expected array literal

                    0 => 'new_int_zero',
                    // @phpstan-ignore-next-line duplicate numeric keys intentional for expected array literal
                    '1' => 'string_one',

                    // @phpstan-ignore-next-line duplicate numeric keys intentional for expected array literal

                    1 => 'int_one',
                ],
            ]);
        });

        test('numeric merge replaces array elements completely', function (): void {
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

            $result = DataMutator::set($data, 'nested', [
                'items' => [
                    [
                        'tags' => ['x'],
                    ],
                    [
                        'tags' => ['y', 'z'],
                    ],
                ],
            ], merge: true);

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

    describe('Merge shortcut method', function (): void {
        test('merge() is shortcut for set() with merge=true', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::merge($data, 'user', [
                'age' => 30,
            ]);

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ]);
        });

        test('merge() with single path and value', function (): void {
            $data = [];
            $result = DataMutator::merge($data, 'user', [
                'name' => 'Alice',
            ]);
            $result = DataMutator::merge($result, 'user', [
                'profile' => [
                    'city' => 'Berlin',
                ],
            ]);

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ]);
        });

        test('merge() with array of paths', function (): void {
            $data = [
                'user' => [
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ];
            $result = DataMutator::merge($data, [
                'user.profile.zip' => '10115',
                'user.name' => 'Alice',
            ]);

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

        test('merge() with numeric indices', function (): void {
            $data = [
                'tags' => ['old1', 'old2'],
            ];
            $result = DataMutator::merge($data, 'tags', ['new1']);

            expect($result)->toBe([
                'tags' => ['new1', 'old2'],
            ]);
        });

        test('merge() with objects', function (): void {
            $dto = new class {
                /** @var array<string, mixed> */
                public array $config = [
                    'debug' => true,
                ];
            };

            DataMutator::merge($dto, 'config', [
                'cache' => 'redis',
            ]);

            expect($dto->config)->toBe([
                'debug' => true,
                'cache' => 'redis',
            ]);
        });
    });

    describe('Unset functionality', function (): void {
        test('can unset simple array values', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ];
            $result = DataMutator::unset($data, 'user.age');

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('can unset nested array values', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                    ],
                ],
            ];
            $result = DataMutator::unset($data, 'user.profile.city');

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                    'profile' => [],
                ],
            ]);
        });

        test('can unset multiple paths at once', function (): void {
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
            $result = DataMutator::unset($data, ['users.0.name', 'users.1.age']);

            expect($result)->toBe([
                'users' => [
                    [],
                    [
                        'name' => 'Bob',
                    ],
                ],
            ]);
        });

        test('can unset from DTO objects', function (): void {
            $dto = new class {
                public string $name = 'Alice';
                public ?string $city = 'Berlin';
            };

            DataMutator::unset($dto, 'city');

            expect($dto->name)->toBe('Alice');
            expect($dto->city)->toBeNull();
        });

        test('can unset private properties from objects', function (): void {
            $dto = new class {
                public string $name = 'Alice';
                private ?string $secret = 'hidden';

                public function getSecret(): ?string
                {
                    return $this->secret;
                }
            };

            DataMutator::unset($dto, 'secret');

            expect($dto->name)->toBe('Alice');
            expect($dto->getSecret())->toBeNull();
        });

        test('unset ignores non-existent paths', function (): void {
            $data = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];
            $result = DataMutator::unset($data, 'user.nonexistent');

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('unset ignores non-existent object properties', function (): void {
            $dto = new class {
                public string $name = 'Alice';
            };

            DataMutator::unset($dto, 'nonexistent');

            expect($dto->name)->toBe('Alice');
        });

        test('can unset with wildcards', function (): void {
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
            $result = DataMutator::unset($data, 'users.*.age');

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

        test('can unset all items via wildcard', function (): void {
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
            $result = DataMutator::unset($data, 'users.*');

            expect($result)->toBe([
                'users' => [],
            ]);
        });

        test('wildcard unset clears entire array', function (): void {
            $data = [
                'items' => ['a', 'b', 'c', 'd'],
            ];
            $result = DataMutator::unset($data, 'items.*');

            expect($result)->toBe([
                'items' => [],
            ]);
        });

        test('wildcard unset with nested arrays', function (): void {
            $data = [
                'categories' => [
                    'tech' => ['php', 'javascript'],
                    'design' => ['ui', 'ux'],
                ],
            ];
            $result = DataMutator::unset($data, 'categories.*');

            expect($result)->toBe([
                'categories' => [],
            ]);
        });

        test('wildcard unset preserves parent structure', function (): void {
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
            $result = DataMutator::unset($data, 'config.*');

            expect($result)->toBe([
                'config' => [],
                'app' => [
                    'name' => 'MyApp',
                ],
            ]);
        });

        test('wildcard unset with mixed object types', function (): void {
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
            $result = DataMutator::unset($data, 'items.*.price');

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

        test('wildcard unset clears mixed object types array', function (): void {
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
            $result = DataMutator::unset($data, 'items.*');

            expect($result)->toBe([
                'items' => [],
            ]);
        });

        test('wildcard unset with empty arrays', function (): void {
            $data = [
                'empty' => [],
                'filled' => ['a', 'b'],
            ];
            $result = DataMutator::unset($data, 'empty.*');

            expect($result)->toBe([
                'empty' => [],
                'filled' => ['a', 'b'],
            ]);
        });

        test('wildcard unset at root level', function (): void {
            $data = [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ];
            $result = DataMutator::unset($data, '*');

            expect($result)->toBe([]);
        });

        test('can unset nested paths with wildcards', function (): void {
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
            $result = DataMutator::unset($data, 'groups.*.users.*.email');

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

        test('can unset from Arrayable objects', function (): void {
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

            $result = DataMutator::unset($arrayable, 'age');

            expect($result)->toBe([
                'name' => 'Alice',
                'city' => 'Berlin',
            ]);
        });

        test('can unset from JsonSerializable objects', function (): void {
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

            $result = DataMutator::unset($jsonSerializable, 'age');

            expect($result)->toBe([
                'name' => 'Alice',
                'city' => 'Berlin',
            ]);
        });

        test('can unset nested values from objects in arrays', function (): void {
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

            $result = DataMutator::unset($data, 'users.*.email');

            /** @var array{users: array<int, object>} $result */
            assert(is_array($result));

            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['users'][0]->name)->toBe('Alice');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['users'][0]->email)->toBeNull();
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['users'][1]->name)->toBe('Bob');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['users'][1]->email)->toBeNull();
        });

        test('wildcard unset clears array of objects', function (): void {
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

            $result = DataMutator::unset($data, 'users.*');

            expect($result)->toBe([
                'users' => [],
            ]);
        });

        test('wildcard unset with multiple levels', function (): void {
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

            $result = DataMutator::unset($data, 'departments.*.teams.*.lead');

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

        test('wildcard unset clears nested arrays completely', function (): void {
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

            $result = DataMutator::unset($data, 'departments.*.teams.*');

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

        test('unset with deep object nesting', function (): void {
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

            DataMutator::unset($dto, 'profile.city');

            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($dto->profile->name)->toBe('Alice');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($dto->profile->city)->toBeNull();
        });

        test('can unset values from Collections', function (): void {
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
            $result = DataMutator::unset($collection, '0.age');

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
        });

        test('can unset values with wildcards from Collections', function (): void {
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
            $result = DataMutator::unset($collection, '*.name');

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
        });

        test('can unset all items from Collection via wildcard', function (): void {
            $collection = collect([
                [
                    'name' => 'Alice',
                ],
                [
                    'name' => 'Bob',
                ],
            ]);
            $result = DataMutator::unset($collection, '*');

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([]);
        });

        test('can unset multiple paths from Collections', function (): void {
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
            $result = DataMutator::unset($collection, ['0.age', '1.city']);

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
        });

        test('can unset nested values from Collections', function (): void {
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
            $result = DataMutator::unset($collection, '*.user.profile.city');

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
        });

        test('can unset from empty Collections', function (): void {
            $collection = collect([]);
            $result = DataMutator::unset($collection, '*');

            expect($result)->toBeInstanceOf(Collection::class);

            /** @var Collection<(int | string), mixed> $result */
            assert($result instanceof Collection);

            expect($result->toArray())->toBe([]);
        });

        test('can unset from Collections with mixed types', function (): void {
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
            $result = DataMutator::unset($collection, '*.type');

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
        });
    });

    describe('Deep Wildcards', function (): void {
        test('can set values with deep wildcards in arrays', function (): void {
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
            $result = DataMutator::set($data, 'users.*.profile.*.country', 'Germany');

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

        test('can unset values with deep wildcards in arrays', function (): void {
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
            $result = DataMutator::unset($data, 'users.*.profile.*.city');

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

        test('can merge values with deep wildcards', function (): void {
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
            $result = DataMutator::merge($data, 'departments.*.teams.*.members', ['NewMember']);

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

        test('deep wildcards with three levels', function (): void {
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
            $result = DataMutator::set($data, 'companies.departments.*.teams.*.projects.*.status', 'active');

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

        test('deep wildcards with mixed array and object structures', function (): void {
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
            $result = DataMutator::set($data, 'organizations.*.divisions.*.locations.*.country', 'USA');

            /** @var array{organizations: array<int, object>} $result */
            assert(is_array($result));

            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['organizations'][0]->divisions[0]['locations'][0]['country'])->toBe('USA');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['organizations'][0]->divisions[0]['locations'][1]['country'])->toBe('USA');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['organizations'][0]->divisions[1]['locations'][0]['country'])->toBe('USA');
            // @phpstan-ignore-next-line property exists on anonymous class in test context
            expect($result['organizations'][1]->divisions[0]['locations'][0]['country'])->toBe('USA');
        });

        test('deep wildcards unset clears nested arrays completely', function (): void {
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
            $result = DataMutator::unset($data, 'regions.*.countries.*.cities.*');

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

        test('deep wildcards with Collections', function (): void {
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
            $result = DataMutator::set($collection, '*.groups.*.items.*.category', 'processed');

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
        });

        test('deep wildcards with empty nested arrays', function (): void {
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
            $result = DataMutator::set($data, 'levels.*.sublevel.*.items.*.status', 'active');

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

        test('deep wildcards with non-array elements are ignored', function (): void {
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
            $result = DataMutator::set($data, 'mixed.*.nested.*.category', 'tagged');

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

        test('deep wildcards unset from Collections', function (): void {
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
            $result = DataMutator::unset($collection, '*.users.*.profile.*.city');

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
        });

        test('deep wildcards with multiple values assignment', function (): void {
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
            $result = DataMutator::set($data, [
                'stores.*.products.*.info.price' => 99.99,
                'stores.*.products.*.info.currency' => 'EUR',
            ]);

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

    describe('Error handling', function (): void {
        test('throws TypeError for unsupported target types', function (): void {
            // @phpstan-ignore-next-line intentional invalid target for error handling test
            expect(fn(): array|object => DataMutator::set('string', 'path', 'value'))
                ->toThrow(TypeError::class);

            // @phpstan-ignore-next-line intentional invalid target for error handling test
            expect(fn(): array|object => DataMutator::set(42, 'path', 'value'))
                ->toThrow(TypeError::class);

            // @phpstan-ignore-next-line intentional invalid target for error handling test
            expect(fn(): array|object => DataMutator::set(true, 'path', 'value'))
                ->toThrow(TypeError::class);
        });

        test('unset throws TypeError for unsupported target types', function (): void {
            // @phpstan-ignore-next-line intentional invalid target for error handling test
            expect(fn(): array|object => DataMutator::unset('string', 'path'))
                ->toThrow(TypeError::class);
        });
    });
});
