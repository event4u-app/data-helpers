<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

describe('DataAccessor', function(): void {
    describe('Constructor', function(): void {
        test('works with array input', function(): void {
            $data = [
                'name' => 'Alice',
                'age' => 30,
            ];
            $accessor = new DataAccessor($data);

            expect($accessor->toArray())->toBe($data);
        });

        test('works with scalar values', function(): void {
            $accessor = new DataAccessor('test');
            expect($accessor->toArray())->toBe(['test']);

            $accessor = new DataAccessor(42);
            expect($accessor->toArray())->toBe([42]);

            $accessor = new DataAccessor(true);
            expect($accessor->toArray())->toBe([true]);
        });

        test('works with valid JSON string', function(): void {
            $json = '{"name": "Alice", "age": 30}';
            $accessor = new DataAccessor($json);

            expect($accessor->toArray())->toBe([
                'name' => 'Alice',
                'age' => 30,
            ]);
        });

        test('treats invalid JSON as plain string', function(): void {
            $invalidJson = '{"name": "Alice", "age":}';
            $accessor = new DataAccessor($invalidJson);

            expect($accessor->toArray())->toBe([$invalidJson]);
        });

        test('works with valid XML string', function(): void {
            $xml = '<root><name>Alice</name><age>30</age></root>';
            $accessor = new DataAccessor($xml);

            $result = $accessor->toArray();
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('age');
            expect($result['name'])->toBe('Alice');
            expect($result['age'])->toBe('30');
        });

        test('treats invalid XML as plain string', function(): void {
            $invalidXml = '<root><name>Alice</name><age>30</root>';
            $accessor = new DataAccessor($invalidXml);

            expect($accessor->toArray())->toBe([$invalidXml]);
        });

        test('works with Arrayable objects', function(): void {
            $arrayable = new class implements Arrayable {
                public function toArray(): array
                {
                    return [
                        'name' => 'Alice',
                        'age' => 30,
                    ];
                }
            };

            $accessor = new DataAccessor($arrayable);
            expect($accessor->toArray())->toBe([
                'name' => 'Alice',
                'age' => 30,
            ]);
        });

        test('works with JsonSerializable objects', function(): void {
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

            $accessor = new DataAccessor($jsonSerializable);
            expect($accessor->toArray())->toBe([
                'name' => 'Bob',
                'age' => 25,
            ]);
        });

        test('works with stdClass objects', function(): void {
            $obj = new stdClass();
            $obj->name = 'Charlie';
            $obj->age = 35;

            $accessor = new DataAccessor($obj);
            expect($accessor->toArray())->toBe([
                'name' => 'Charlie',
                'age' => 35,
            ]);
        });

        test('works with Collections', function(): void {
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

            $accessor = new DataAccessor($collection);
            expect($accessor->toArray())->toBe([
                [
                    'name' => 'Alice',
                    'age' => 30,
                ],
                [
                    'name' => 'Bob',
                    'age' => 25,
                ],
            ]);
        });

        test('works with Laravel Models', function(): void {
            $model = new class extends Model {
                /** @var array<string, mixed> */
                protected $attributes = [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                    'age' => 30,
                ];
            };

            $accessor = new DataAccessor($model);
            expect($accessor->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => 30,
            ]);
        });

        test('works with nested Collections', function(): void {
            $collection = collect([
                'users' => collect([
                    [
                        'name' => 'Alice',
                    ],
                    [
                        'name' => 'Bob',
                    ],
                ]),
                'settings' => collect([
                    'theme' => 'dark',
                ]),
            ]);

            $accessor = new DataAccessor($collection);
            $result = $accessor->toArray();

            // Collections are preserved as Collection objects in the internal data
            expect($result)->toHaveKey('users');
            expect($result)->toHaveKey('settings');
            expect($result['users'])->toBeInstanceOf(Collection::class);
            expect($result['settings'])->toBeInstanceOf(Collection::class);
        });
    });

    describe('Get method - Basic functionality', function(): void {
        test('can get simple paths', function(): void {
            $data = [
                'name' => 'Alice',
                'age' => 30,
            ];
            $accessor = new DataAccessor($data);

            expect($accessor->get('name'))->toBe('Alice');
            expect($accessor->get('age'))->toBe(30);
        });

        test('can get nested paths', function(): void {
            $data = [
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'age' => 30,
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            expect($accessor->get('user.profile.name'))->toBe('Alice');
            expect($accessor->get('user.profile.age'))->toBe(30);
        });

        test('returns default value for non-existent paths', function(): void {
            $data = [
                'name' => 'Alice',
            ];
            $accessor = new DataAccessor($data);

            expect($accessor->get('name', 'default'))->toBe('Alice');
            expect($accessor->get('nonexistent', 'default'))->toBe('default');
            expect($accessor->get('nonexistent'))->toBeNull();
        });
    });

    describe('Get method - Wildcard functionality', function(): void {
        test('works with single wildcard', function(): void {
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
            $accessor = new DataAccessor($data);

            $result = $accessor->get('users.*.name');
            $expected = [
                'users.0.name' => 'Alice',
                'users.1.name' => 'Bob',
            ];

            expect($result)->toBe($expected);
        });

        test('works with multiple wildcards', function(): void {
            $data = [
                'orders' => [
                    [
                        'id' => 1,
                        'items' => [
                            [
                                'id' => 'A1',
                            ],
                            [
                                'id' => 'A2',
                            ],
                        ],
                    ],
                    [
                        'id' => 2,
                        'items' => [
                            [
                                'id' => 'B1',
                            ],
                            [
                                'id' => 'B2',
                            ],
                        ],
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('orders.*.items.*.id');
            $expected = [
                'orders.0.items.0.id' => 'A1',
                'orders.0.items.1.id' => 'A2',
                'orders.1.items.0.id' => 'B1',
                'orders.1.items.1.id' => 'B2',
            ];

            expect($result)->toBe($expected);
        });

        test('returns null for wildcard on non-array', function(): void {
            $data = [
                'user' => 'Alice',
            ];
            $accessor = new DataAccessor($data);

            expect($accessor->get('user.*'))->toBeNull();
            expect($accessor->get('user.*', 'default'))->toBe('default');
        });

        test('returns empty array for wildcard on empty array', function(): void {
            $data = [
                'users' => [],
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('users.*.name');
            expect($result)->toBe([]);
        });

        test('works with wildcard at root level', function(): void {
            $data = [
                'Alice',
                'Bob',
                'Charlie',
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('*');
            $expected = [
                '0' => 'Alice',
                '1' => 'Bob',
                '2' => 'Charlie',
            ];

            expect($result)->toBe($expected);
        });

        test('works with complex nested structures', function(): void {
            $data = [
                'companies' => [
                    [
                        'name' => 'Company A',
                        'departments' => [
                            [
                                'name' => 'IT',
                                'employees' => [
                                    [
                                        'name' => 'Alice',
                                        'role' => 'Developer',
                                    ],
                                    [
                                        'name' => 'Bob',
                                        'role' => 'Manager',
                                    ],
                                ],
                            ],
                            [
                                'name' => 'HR',
                                'employees' => [
                                    [
                                        'name' => 'Charlie',
                                        'role' => 'Recruiter',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('companies.*.departments.*.employees.*.name');
            $expected = [
                'companies.0.departments.0.employees.0.name' => 'Alice',
                'companies.0.departments.0.employees.1.name' => 'Bob',
                'companies.0.departments.1.employees.0.name' => 'Charlie',
            ];

            expect($result)->toBe($expected);
        });

        test('works with mixed key types', function(): void {
            $data = [
                'users' => [
                    'admin' => [
                        'name' => 'Admin User',
                    ],
                    'guest' => [
                        'name' => 'Guest User',
                    ],
                    0 => [
                        'name' => 'Indexed User',
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('users.*.name');
            $expected = [
                'users.admin.name' => 'Admin User',
                'users.guest.name' => 'Guest User',
                'users.0.name' => 'Indexed User',
            ];

            expect($result)->toBe($expected);
        });
    });

    describe('Get method - Return type behavior', function(): void {
        test('non-wildcard paths return scalar values', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            expect($accessor->get('users.0.name'))->toBe('Alice');
        });

        test('wildcard paths always return arrays', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('users.*.name');
            expect($result)->toBeArray();
            expect($result)->toBe([
                'users.0.name' => 'Alice',
            ]);
        });
    });

    describe('Get method - Special values', function(): void {
        test('handles null values correctly', function(): void {
            $data = [
                'users' => [
                    [
                        'name' => 'Alice',
                        'email' => null,
                    ],
                    [
                        'name' => 'Bob',
                        'email' => 'bob@example.com',
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('users.*.email');
            $expected = [
                'users.0.email' => null,
                'users.1.email' => 'bob@example.com',
            ];

            expect($result)->toBe($expected);
        });

        test('handles boolean and numeric values correctly', function(): void {
            $data = [
                'settings' => [
                    [
                        'enabled' => true,
                        'count' => 5,
                    ],
                    [
                        'enabled' => false,
                        'count' => 0,
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $enabledResult = $accessor->get('settings.*.enabled');
            $countResult = $accessor->get('settings.*.count');

            expect($enabledResult)->toBe([
                'settings.0.enabled' => true,
                'settings.1.enabled' => false,
            ]);

            expect($countResult)->toBe([
                'settings.0.count' => 5,
                'settings.1.count' => 0,
            ]);
        });
    });

    describe('toArray method', function(): void {
        test('returns original data unchanged', function(): void {
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
            $accessor = new DataAccessor($data);

            expect($accessor->toArray())->toBe($data);
        });
    });

    describe('Collection and Model support', function(): void {
        test('can access Collection data with wildcards', function(): void {
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
            $accessor = new DataAccessor([
                'users' => $collection,
            ]);

            $result = $accessor->get('users.*.name');
            expect($result)->toBe([
                'users.0.name' => 'Alice',
                'users.1.name' => 'Bob',
            ]);
        });

        test('can access nested Collections', function(): void {
            $data = [
                'departments' => collect([
                    [
                        'name' => 'IT',
                        'employees' => collect([
                            [
                                'name' => 'Alice',
                            ],
                            [
                                'name' => 'Bob',
                            ],
                        ]),
                    ],
                    [
                        'name' => 'HR',
                        'employees' => collect([
                            [
                                'name' => 'Charlie',
                            ],
                        ]),
                    ],
                ]),
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('departments.*.employees.*.name');
            expect($result)->toBe([
                'departments.0.employees.0.name' => 'Alice',
                'departments.0.employees.1.name' => 'Bob',
                'departments.1.employees.0.name' => 'Charlie',
            ]);
        });

        test('can access Laravel Model attributes', function(): void {
            $model = new class extends Model {
                /** @var array<string, mixed> */
                protected $attributes = [
                    'name' => 'Alice',
                    'profile' => [
                        'city' => 'Berlin',
                        'country' => 'Germany',
                    ],
                ];
            };
            $accessor = new DataAccessor([
                'user' => $model,
            ]);

            expect($accessor->get('user.name'))->toBe('Alice');
            expect($accessor->get('user.profile.city'))->toBe('Berlin');
        });

        test('can access Models with wildcards', function(): void {
            $models = [
                new class extends Model {
                    /** @var array<string, mixed> */
                    protected $attributes = [
                        'name' => 'Alice',
                        'role' => 'admin',
                    ];
                },
                new class extends Model {
                    /** @var array<string, mixed> */
                    protected $attributes = [
                        'name' => 'Bob',
                        'role' => 'user',
                    ];
                },
            ];
            $accessor = new DataAccessor([
                'users' => $models,
            ]);

            $result = $accessor->get('users.*.name');
            expect($result)->toBe([
                'users.0.name' => 'Alice',
                'users.1.name' => 'Bob',
            ]);
        });

        test('can traverse Collection with numeric keys', function(): void {
            $collection = collect([
                'first' => [
                    'value' => 1,
                ],
                'second' => [
                    'value' => 2,
                ],
                0 => [
                    'value' => 3,
                ],
            ]);
            $accessor = new DataAccessor($collection);

            expect($accessor->get('first.value'))->toBe(1);
            expect($accessor->get('second.value'))->toBe(2);
            expect($accessor->get('0.value'))->toBe(3);
        });

        test('handles Collection with has() method', function(): void {
            $collection = collect([
                'name' => 'Alice',
                'age' => 30,
            ]);
            $accessor = new DataAccessor($collection);

            expect($accessor->get('name'))->toBe('Alice');
            expect($accessor->get('age'))->toBe(30);
            expect($accessor->get('nonexistent', 'default'))->toBe('default');
        });
    });

    describe('Deep Wildcards and Complex Structures', function(): void {
        test('handles deep wildcards with Collections and Models', function(): void {
            $data = [
                'companies' => collect([
                    [
                        'name' => 'Company A',
                        'departments' => collect([
                            new class extends Model {
                                /** @var array<string, mixed> */
                                protected $attributes = [
                                    'name' => 'IT',
                                    'employees' => [
                                        [
                                            'name' => 'Alice',
                                            'skills' => ['PHP', 'Laravel'],
                                        ],
                                        [
                                            'name' => 'Bob',
                                            'skills' => ['JavaScript', 'Vue'],
                                        ],
                                    ],
                                ];
                            },
                        ]),
                    ],
                ]),
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('companies.*.departments.*.employees.*.name');
            expect($result)->toBe([
                'companies.0.departments.0.employees.0.name' => 'Alice',
                'companies.0.departments.0.employees.1.name' => 'Bob',
            ]);
        });

        test('handles mixed Collection and array structures', function(): void {
            $data = [
                'regions' => collect([
                    [
                        'name' => 'Europe',
                        'countries' => [
                            [
                                'name' => 'Germany',
                                'cities' => collect(['Berlin', 'Munich']),
                            ],
                            [
                                'name' => 'France',
                                'cities' => collect(['Paris', 'Lyon']),
                            ],
                        ],
                    ],
                ]),
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('regions.*.countries.*.cities.*');
            expect($result)->toBe([
                'regions.0.countries.0.cities.0' => 'Berlin',
                'regions.0.countries.0.cities.1' => 'Munich',
                'regions.0.countries.1.cities.0' => 'Paris',
                'regions.0.countries.1.cities.1' => 'Lyon',
            ]);
        });

        test('handles empty Collections in wildcard paths', function(): void {
            $data = [
                'groups' => collect([
                    [
                        'name' => 'Group A',
                        'items' => collect([]),
                    ],
                    [
                        'name' => 'Group B',
                        'items' => collect(['item1', 'item2']),
                    ],
                ]),
            ];
            $accessor = new DataAccessor($data);

            $result = $accessor->get('groups.*.items.*');
            expect($result)->toBe([
                'groups.1.items.0' => 'item1',
                'groups.1.items.1' => 'item2',
            ]);
        });

        test('handles Collection with non-array elements', function(): void {
            $collection = collect([
                [
                    'data' => collect([
                        'value' => 1,
                    ]),
                ],
                [
                    'data' => 'string_value',
                ],
                [
                    'data' => collect([
                        'value' => 2,
                    ]),
                ],
            ]);
            $accessor = new DataAccessor($collection);

            $result = $accessor->get('*.data.value');
            expect($result)->toBe([
                '0.data.value' => 1,
                '2.data.value' => 2,
            ]);
        });
    });
});
