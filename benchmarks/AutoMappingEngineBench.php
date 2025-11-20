<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataMapper\Support\AutoMappingEngine;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
final class AutoMappingEngineBench
{
    /**
     * @var array<string, mixed>
     */
    private array $deepNestedSource;

    /**
     * @var array<string, mixed>
     */
    private array $largeListSource;

    public function setUp(): void
    {
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
                                        ],
                                    ),
                                ],
                            ),
                        ],
                    ),
                ],
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
                ],
            ),
        ];
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Groups(['docs'])]
    public function benchFlattenDeepNested(): void
    {
        AutoMappingEngine::flattenSourcePaths($this->deepNestedSource, true, '', true);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchFlattenLargeList(): void
    {
        AutoMappingEngine::flattenSourcePaths($this->largeListSource, true, '', true);
    }
}

