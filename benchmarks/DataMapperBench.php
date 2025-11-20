<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataMapper;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class DataMapperBench
{
    /** @var array<string, mixed> */
    private array $simpleSource;
    /** @var array<string, mixed> */
    private array $nestedSource;
    /** @var array<string, mixed> */
    private array $deepNestedSource;
    /** @var array<string, mixed> */
    private array $largeListSource;
    /** @var array<string, string> */
    private array $simpleMapping;

    public function setUp(): void
    {
        $this->simpleSource = [
            'firstName' => 'Alice',
            'lastName' => 'Smith',
            'email' => 'alice@example.com',
        ];

        $this->nestedSource = [
            'user' => [
                'profile' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Smith',
                ],
                'contact' => [
                    'email' => 'alice@example.com',
                    'phone' => '+1234567890',
                ],
            ],
        ];

        $this->deepNestedSource = [
            'companies' => array_fill(
                0,
                2,
                [
                    'name' => 'Acme Inc',
                    'departments' => array_fill(
                        0,
                        5,
                        [
                            'name' => 'Engineering',
                            'teams' => array_fill(
                                0,
                                3,
                                [
                                    'name' => 'Backend',
                                    'employees' => array_fill(
                                        0,
                                        10,
                                        [
                                            'name' => 'Employee',
                                            'email' => 'employee@example.com',
                                            'profile' => [
                                                'age' => 30,
                                                'city' => 'Berlin',
                                            ],
                                        ]
                                    ),
                                ]
                            ),
                        ]
                    ),
                ]
            ),
        ];

        $this->largeListSource = [
            'users' => array_fill(
                0,
                200,
                [
                    'name' => 'User',
                    'email' => 'user@example.com',
                    'profile' => [
                        'age' => 30,
                        'city' => 'Berlin',
                    ],
                ]
            ),
        ];

        $this->simpleMapping = [
            'name' => 'firstName',
            'surname' => 'lastName',
            'mail' => 'email',
        ];
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Groups(['docs'])]
    public function benchSimpleMapping(): void
    {
        DataMapper::source($this->simpleSource)
            ->target([])
            ->template($this->simpleMapping)
            ->map();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Groups(['docs'])]
    public function benchNestedMapping(): void
    {
        $mapping = [
            'profile.name' => 'user.profile.firstName',
            'profile.surname' => 'user.profile.lastName',
            'contact.email' => 'user.contact.email',
        ];
        DataMapper::source($this->nestedSource)
            ->target([])
            ->template($mapping)
            ->map();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Groups(['docs'])]
    public function benchAutoMap(): void
    {
        $target = ['firstName' => null, 'lastName' => null, 'email' => null];
        DataMapper::source($this->simpleSource)
            ->target($target)
            ->autoMap();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Groups(['docs'])]
    public function benchMapFromTemplate(): void
    {
        $template = [
            'user' => [
                'name' => '{{ firstName }}',
                'email' => '{{ email }}',
            ],
        ];
        DataMapper::source($this->simpleSource)
            ->template($template)
            ->map();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAutoMapDeep(): void
    {
        $source = [
            'users' => [
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
                [
                    'name' => 'Bob',
                    'email' => 'bob@example.com',
                ],
                [
                    'name' => 'Carol',
                    'email' => 'carol@example.com',
                ],
            ],
        ];

        DataMapper::source($source)
            ->target([])
            ->deep(true)
            ->autoMap();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAutoMapDeepNestedWildcards(): void
    {
        DataMapper::source($this->deepNestedSource)
            ->target([])
            ->deep(true)
            ->autoMap();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAutoMapDeepLargeList(): void
    {
        DataMapper::source($this->largeListSource)
            ->target([])
            ->deep(true)
            ->autoMap();
    }
}
