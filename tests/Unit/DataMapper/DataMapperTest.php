<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use Illuminate\Database\Eloquent\Model;

describe('DataMapper', function(): void {
    describe('Simple mapping', function(): void {
        test('maps nested key to deep target (example)', function(): void {
            $source = [
                'key1' => [
                    'subkey3' => 'Hello World',
                ],
            ];

            $target = [];

            $mapping = [
                'target2.subtarget4.subsub9' => '{{ key1.subkey3 }}',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'target2' => [
                    'subtarget4' => [
                        'subsub9' => 'Hello World',
                    ],
                ],
            ]);
        });

        test('maps multiple simple fields', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];
            $target = [];
            $mapping = [
                'profile.fullname' => '{{ user.name }}',
                'profile.contact.email' => '{{ user.email }}',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        test('maps multiple nested fields', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];
            $target = [];
            $mapping = [
                'profile' => [
                    'fullname' => '{{ user.name }}',
                    'contact' => [
                        'email' => '{{ user.email }}',
                    ],
                ],
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        test('supports wildcards in source and target', function(): void {
            $source = [
                'users' => [
                    [
                        'email' => 'alice@example.com',
                    ],
                    [
                        'email' => 'bob@example.com',
                    ],
                ],
            ];
            $target = [];
            $mapping = [
                'emails.*' => '{{ users.*.email }}',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'emails' => [
                    0 => 'alice@example.com',
                    1 => 'bob@example.com',
                ],
            ]);
        });

        test('root level numeric keys mapping', function(): void {
            $source = ['Alice', 'Bob', 'Charlie'];
            $target = [];
            $mapping = [
                'first' => '{{ 0 }}',
                'second' => '{{ 1 }}',
                'third' => '{{ 2 }}',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'first' => 'Alice',
                'second' => 'Bob',
                'third' => 'Charlie',
            ]);
        });

        test('mixed key types in source paths', function(): void {
            $source = [
                'string_key' => 'value1',
                0 => 'value2',
                'nested' => [
                    'sub' => 'value3',
                ],
            ];
            $target = [];
            $mapping = [
                'result.string' => '{{ string_key }}',
                'result.numeric' => '{{ 0 }}',
                'result.nested' => '{{ nested.sub }}',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'result' => [
                    'string' => 'value1',
                    'numeric' => 'value2',
                    'nested' => 'value3',
                ],
            ]);
        });
    });

    describe('Structured mapping (source/target entries)', function(): void {
        test('maps model and array into shared DTO using source/target mappings', function(): void {
            $userModel = new class extends Model {
            };
            $userModel->setRawAttributes([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);
            $address = [
                'street' => 'Main Street 1',
                'zip' => '10115',
            ];
            $resultDTO = [];

            $structured = [
                [
                    'source' => $userModel,
                    'sourceMapping' => ['name', 'email'],
                    'target' => $resultDTO,
                    'targetMapping' => ['profile.fullname', 'profile.contact.email'],
                ],
                [
                    'source' => $address,
                    'sourceMapping' => ['street', 'zip'],
                    // omit 'target' here to accumulate into previous target
                    'targetMapping' => ['profile.address.street', 'profile.address.zip'],
                ],
            ];

            $result = DataMapper::source(null)
                ->target(null)
                ->template($structured)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                    'address' => [
                        'street' => 'Main Street 1',
                        'zip' => '10115',
                    ],
                ],
            ]);
        })->group('laravel');

        test('mapMany returns array of results for each mapping', function(): void {
            $userModel = new class extends Model {
            };
            $userModel->setRawAttributes([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);
            $resultDTO = [];

            $template = [
                'profile.fullname' => '{{ name }}',
                'profile.contact.email' => '{{ email }}',
            ];

            $results = DataMapper::template($template)
                ->target($resultDTO)
                ->mapMany([
                    ['source' => $userModel, 'target' => $resultDTO],
                ]);

            expect($results)->toHaveCount(1);
            expect($results[0]->getTarget())->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        test('mapMany respects global skipNull=false and per-entry override', function(): void {
            $userModel = new class extends Model {
            };
            $userModel->setRawAttributes([
                'name' => 'Alice',
                'email' => null,
            ]);

            $template = [
                'profile.fullname' => '{{ name }}',
                'profile.contact.email' => '{{ email }}',
            ];

            // Global skipNull=false => null included
            $results = DataMapper::template($template)
                ->skipNull(false)
                ->mapMany([
                    ['source' => $userModel, 'target' => []],
                ]);

            expect($results)->toHaveCount(1);
            expect($results[0]->getTarget())->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => null,
                    ],
                ],
            ]);

            // Per-entry override skipNull=true => null skipped
            $results2 = DataMapper::template($template)
                ->skipNull(true)
                ->mapMany([
                    ['source' => $userModel, 'target' => []],
                ]);

            expect($results2[0]->getTarget())->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                ],
            ]);
        })->group('laravel');
    })->group('laravel');

    describe('New capabilities', function(): void {
        test('structured mapping supports associative mapping pairs', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];
            $dto = [];

            $structured = [
                [
                    'source' => $source,
                    'target' => $dto,
                    'mapping' => [
                        'user.name' => 'profile.fullname',
                        'user.email' => 'profile.contact.email',
                    ],
                ],
            ];

            $result = DataMapper::source(null)
                ->target(null)
                ->template($structured)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        test('structured mapping supports list of [src, dst] pairs', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];
            $dto = [];

            $structured = [
                [
                    'source' => $source,
                    'target' => $dto,
                    'mapping' => [
                        ['user.name', 'profile.fullname'],
                        ['user.email', 'profile.contact.email'],
                    ],
                ],
            ];

            $result = DataMapper::source(null)
                ->target(null)
                ->template($structured)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'profile' => [
                    'fullname' => 'Alice',
                    'contact' => [
                        'email' => 'alice@example.com',
                    ],
                ],
            ]);
        });

        test('skips null values by default - simple mapping', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
            ];
            $target = [];
            $mapping = [
                'user.name' => '{{ name }}',
                'user.email' => '{{ email }}',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('skips null values by default - structured mapping', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
            ];
            $dto = [];

            $structured = [
                [
                    'source' => $source,
                    'target' => $dto,
                    'mapping' => [
                        'name' => 'user.name',
                        'email' => 'user.email',
                    ],
                ],
            ];

            $result = DataMapper::source(null)
                ->target(null)
                ->template($structured)
                ->map()
                ->getTarget();

            expect($result)->toBe([
                'user' => [
                    'name' => 'Alice',
                ],
            ]);
        });

        test('throws helpful error for mismatched source/target mapping lengths', function(): void {
            $structured = [
                [
                    'source' => [
                        'a' => 1,
                        'b' => 2,
                    ],
                    'target' => [],
                    'sourceMapping' => ['a', 'b'],
                    'targetMapping' => ['x'],
                ],
            ];

            expect(function() use ($structured): mixed {
                MapperExceptions::setCollectExceptionsEnabled(false);
                return DataMapper::source(null)
                    ->target(null)
                    ->template($structured)
                    ->map()
                    ->getTarget();
            })->toThrow(InvalidArgumentException::class, 'source=2, target=1');
        });

        test('throws when mapping pair is invalid', function(): void {
            $structured = [
                [
                    'source' => [
                        'name' => 'Alice',
                    ],
                    'target' => [],
                    'mapping' => [['name']], // invalid pair
                ],
            ];

            expect(function() use ($structured): mixed {
                MapperExceptions::setCollectExceptionsEnabled(false);
                return DataMapper::source(null)
                    ->target(null)
                    ->template($structured)
                    ->map()
                    ->getTarget();
            })->toThrow(InvalidArgumentException::class, 'Invalid mapping pair');
        });

        test('throws when mapping paths are not strings', function(): void {
            $structured = [
                [
                    'source' => [
                        'name' => 'Alice',
                    ],
                    'target' => [],
                    'mapping' => [[123, 'user.name']],
                ],
            ];

            expect(function() use ($structured): mixed {
                MapperExceptions::setCollectExceptionsEnabled(false);
                return DataMapper::source(null)
                    ->target(null)
                    ->template($structured)
                    ->map()
                    ->getTarget();
            })->toThrow(InvalidArgumentException::class, 'Mapping paths must be strings.');
        });
    });

    test('does not skip null when skipNull param is false - simple mapping', function(): void {
        $source = [
            'name' => 'Alice',
            'email' => null,
        ];
        $target = [];
        $mapping = [
            'user.name' => '{{ name }}',
            'user.email' => '{{ email }}',
        ];

        $result = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'user' => [
                'name' => 'Alice',
                'email' => null,
            ],
        ]);
    });

    test('structured mapping inherits skipNull param=false; can override per entry', function(): void {
        $source = [
            'name' => 'Alice',
            'email' => null,
        ];
        $dto = [];

        // Inherit skipNull=false from method call (null included)
        $structured = [
            [
                'source' => $source,
                'target' => $dto,
                'mapping' => [
                    'name' => 'user.name',
                    'email' => 'user.email',
                ],
            ],
        ];

        $result = DataMapper::source(null)
            ->target(null)
            ->template($structured)
            ->skipNull(false)
            ->map()
            ->getTarget();
        expect($result)->toBe([
            'user' => [
                'name' => 'Alice',
                'email' => null,
            ],
        ]);

        // Override per entry: skipNull=true (null skipped)
        $structuredOverride = [
            [
                'source' => $source,
                'target' => [],
                'skipNull' => true,
                'mapping' => [
                    'name' => 'user.name',
                    'email' => 'user.email',
                ],
            ],
        ];

        $result2 = DataMapper::source(null)
            ->target(null)
            ->template($structuredOverride)
            ->skipNull(false)
            ->map()
            ->getTarget();
        expect($result2)->toBe([
            'user' => [
                'name' => 'Alice',
            ],
        ]);
    });
});

describe('Template mapping', function(): void {
    test('builds structure from array template and sources', function(): void {
        $userModel = new class extends Model {
        };
        $userModel->setRawAttributes([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $address = [
            'street' => 'Main Street 1',
            'zip' => '10115',
        ];

        $sources = [
            'user' => $userModel,
            'addr' => $address,
        ];

        $template = [
            'profile' => [
                'fullname' => '{{ user.name }}',
                'email' => '{{ user.email }}',
                'address' => [
                    'street' => '{{ addr.street }}',
                    'zip' => '{{ addr.zip }}',
                ],
            ],
        ];

        $result = DataMapper::template($template)->sources($sources)->map()->getTarget();

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
    })->group('laravel');

    test('supports JSON template and wildcard with null skipping', function(): void {
        $sources = [
            'src' => [
                'users' => [
                    [
                        'email' => 'a@example.com',
                    ],
                    [
                        'email' => null,
                    ],
                    [
                        'email' => 'b@example.com',
                    ],
                ],
            ],
        ];

        $template = [
            'emails' => '{{ src.users.*.email }}',
        ];

        $result = DataMapper::template($template)->sources($sources)->skipNull(true)->map()->getTarget();

        expect($result)->toBe([
            'emails' => [
                0 => 'a@example.com',
                2 => 'b@example.com',
            ],
        ]);
    });

    test('includes nulls when skipNull=false', function(): void {
        $sources = [
            'src' => [
                'value' => null,
            ],
        ];
        $template = [
            'out' => '{{ src.value }}',
        ];
        $result = DataMapper::template($template)->sources($sources)->skipNull(false)->map()->getTarget();
        expect($result)->toBe([
            'out' => null,
        ]);
    });

    test('literal values are preserved; unknown alias returns null', function(): void {
        $sources = [
            'user' => [
                'name' => 'Alice',
            ],
        ];
        $template = [
            'title' => 'Hello',
            'unknown' => '{{ foo.bar }}',
            'fullname' => '{{ user.name }}',
        ];
        $result = DataMapper::template($template)->sources($sources)->map()->getTarget();
        expect($result)->toBe([
            'title' => 'Hello',
            // 'unknown' is skipped because it's null and skipNull=true (default)
            'fullname' => 'Alice',
        ]);
    });
});

describe('Reindexing in map and mapMany', function(): void {
    test('simple map wildcard preserves gaps by default and reindexes when true', function(): void {
        $source = [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => null,
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ];

        $mapping = [
            'emails.*' => '{{ users.*.email }}',
        ];

        $resultDefault = DataMapper::source($source)
            ->target([])
            ->template($mapping)
            ->map()
            ->getTarget();
        expect($resultDefault)->toBe([
            'emails' => [
                0 => 'a@example.com',
                2 => 'b@example.com',
            ],
        ]);

        $resultReindexed = DataMapper::source($source)
            ->target([])
            ->template($mapping)
            ->reindexWildcard(true)
            ->map()
            ->getTarget();
        expect($resultReindexed)->toBe([
            'emails' => ['a@example.com', 'b@example.com'],
        ]);
    });

    test('mapMany wildcard preserves gaps by default and reindexes when true', function(): void {
        $source = [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => null,
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ];

        $template = [
            'emails.*' => '{{ users.*.email }}',
        ];

        $resultsDefault = DataMapper::template($template)
            ->skipNull(true)
            ->reindexWildcard(false)
            ->mapMany([
                ['source' => $source, 'target' => []],
            ]);

        expect($resultsDefault[0]->getTarget())->toBe([
            'emails' => [
                0 => 'a@example.com',
                2 => 'b@example.com',
            ],
        ]);

        $resultsReindexed = DataMapper::template($template)
            ->skipNull(true)
            ->reindexWildcard(true)
            ->mapMany([
                ['source' => $source, 'target' => []],
            ]);

        expect($resultsReindexed[0]->getTarget())->toBe([
            'emails' => ['a@example.com', 'b@example.com'],
        ]);
    });
});

describe('Structured mapping per-entry reindex override', function(): void {
    test('entry can enable reindexing when global is false', function(): void {
        $source = [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => null,
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ];

        $result = DataMapper::source(null)
            ->target([])
            ->template([
                [
                    'source' => $source,
                    'target' => [],
                    'sourceMapping' => ['users.*.email'],
                    'targetMapping' => ['emails.*'],
                    'skipNull' => true,
                    'reindexWildcard' => true,
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'emails' => ['a@example.com', 'b@example.com'],
        ]);
    });

    test('entry can disable reindexing when global is true', function(): void {
        $source = [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => null,
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ];

        $result = DataMapper::source(null)
            ->target([])
            ->template([
                [
                    'source' => $source,
                    'target' => [],
                    'sourceMapping' => ['users.*.email'],
                    'targetMapping' => ['emails.*'],
                    'skipNull' => true,
                    'reindexWildcard' => false,
                ],
            ])
            ->reindexWildcard(true)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'emails' => [
                0 => 'a@example.com',
                2 => 'b@example.com',
            ],
        ]);
    });
});

test('JSON template with wildcard can reindex sequentially', function(): void {
    $sources = [
        'src' => [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => null,
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ],
    ];

    $template = [
        'emails' => '{{ src.users.*.email }}',
    ];

    $result = DataMapper::template($template)->sources($sources)->skipNull(true)->reindexWildcard(
        true
    )->map()->getTarget();

    expect($result)->toBe([
        'emails' => ['a@example.com', 'b@example.com'],
    ]);
});

describe('Inverse template mapping (apply values to targets)', function(): void {
    test('writes values into DTO and array targets using template aliases', function(): void {
        $userDto = new class {
            /** @var null|string */
            public $name = null;

            /** @var null|string */
            public $email = null;
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

        $res = DataMapper::source($data)->target($targets)->template($template)->map()->getTarget();

        $acc = new DataAccessor($res['user']);
        expect($acc->get('name'))->toBe('Alice');
        expect($acc->get('email'))->toBe('alice@example.com');
        expect($res['addr'])->toBe([
            'street' => 'Main Street 1',
        ]);
    });

    test('wildcard write preserves gaps by default', function(): void {
        $targets = [
            'people' => [],
        ];
        $template = [
            'names' => '{{ people.*.name }}',
        ];
        $data = [
            'names' => ['Alice', null, 'Bob'],
        ];

        $res = DataMapper::source($data)->target($targets)->template($template)->skipNull(true)->reindexWildcard(
            false
        )->map()->getTarget();

        expect($res['people'])->toBe([
            0 => [
                'name' => 'Alice',
            ],
            2 => [
                'name' => 'Bob',
            ],
        ]);
    });

    test('wildcard write can reindex sequentially', function(): void {
        $targets = [
            'people' => [],
        ];
        $template = [
            'names' => '{{ people.*.name }}',
        ];
        $data = [
            'names' => ['Alice', null, 'Bob'],
        ];

        $res = DataMapper::source($data)->target($targets)->template($template)->skipNull(true)->reindexWildcard(
            true
        )->map()->getTarget();

        expect($res['people'])->toBe([
            [
                'name' => 'Alice',
            ],
            [
                'name' => 'Bob',
            ],
        ]);
    });
});

describe('Transforms', function(): void {
    test('structured source/target mappings support transforms by index', function(): void {
        $source = [
            'name' => 'Alice',
            'email' => 'ALICE@EXAMPLE.COM',
        ];
        $res = DataMapper::source(null)
            ->target([])
            ->template([
                [
                    'source' => $source,
                    'target' => [],
                    'sourceMapping' => ['name', 'email'],
                    'targetMapping' => ['out.nameUpper', 'out.emailLower'],
                    'transforms' => ['strtoupper', 'strtolower'],
                ],
            ])
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'out' => [
                'nameUpper' => 'ALICE',
                'emailLower' => 'alice@example.com',
            ],
        ]);
    });

    test('structured associative mapping supports transforms keyed by source path', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
                'email' => 'ALICE@EXAMPLE.COM',
            ],
        ];
        $dto = [];
        $res = DataMapper::source(null)
            ->target(null)
            ->template([
                [
                    'source' => $source,
                    'target' => $dto,
                    'mapping' => [
                        'user.name' => 'profile.fullname',
                        'user.email' => 'profile.email',
                    ],
                    'transforms' => [
                        'user.name' => 'strtoupper',
                        'user.email' => 'strtolower',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'profile' => [
                'fullname' => 'ALICE',
                'email' => 'alice@example.com',
            ],
        ]);
    });

    test('structured list-of-pairs supports transforms aligned by index', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
                'email' => 'ALICE@EXAMPLE.COM',
            ],
        ];
        $dto = [];
        $res = DataMapper::source(null)
            ->target(null)
            ->template([
                [
                    'source' => $source,
                    'target' => $dto,
                    'mapping' => [
                        ['user.name', 'profile.fullname'],
                        ['user.email', 'profile.email'],
                    ],
                    'transforms' => ['strtoupper', 'strtolower'],
                ],
            ])
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'profile' => [
                'fullname' => 'ALICE',
                'email' => 'alice@example.com',
            ],
        ]);
    });

    test('transforms apply to each wildcard element', function(): void {
        $source = [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => null,
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ];

        $res = DataMapper::source(null)
            ->target([])
            ->template([
                [
                    'source' => $source,
                    'target' => [],
                    'sourceMapping' => ['users.*.email'],
                    'targetMapping' => ['out.*'],
                    'skipNull' => true,
                    'reindexWildcard' => true,
                    'transforms' => [
                        static fn(mixed $v): mixed => is_string($v) ? strtoupper($v) : $v,
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'out' => ['A@EXAMPLE.COM', 'B@EXAMPLE.COM'],
        ]);
    });
});
