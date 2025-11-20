<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataMapper\Support\MappingEngine;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class WildcardMappingBench
{
    /** @var array<int, string> */
    private array $wildcardValues200;

    /** @var array<int, string> */
    private array $wildcardValues1200;

    /** @var array<int, string> */
    private array $wildcardValues200Deep;

    /** @var array<int, string> */
    private array $wildcardValues1200Deep;

    /** @var array<string, mixed> */
    private array $wildcardSource200;

    /** @var array<string, mixed> */
    private array $wildcardSource1200;

    /** @var array<string, mixed> */
    private array $wildcardSource200Deep;

    /** @var array<string, mixed> */
    private array $wildcardSource1200Deep;

    public function setUp(): void
    {
        $this->wildcardValues200 = array_fill(0, 200, 'user@example.com');
        $this->wildcardValues1200 = array_fill(0, 1200, 'employee@example.com');

        $this->wildcardValues200Deep = array_fill(0, 200, 'user@example.com');
        $this->wildcardValues1200Deep = array_fill(0, 1200, 'employee@example.com');

        $this->wildcardSource200 = [
            'users' => array_fill(
                0,
                200,
                [
                    'email' => 'user@example.com',
                ],
            ),
        ];

        $this->wildcardSource1200 = [
            'employees' => array_fill(
                0,
                1200,
                [
                    'email' => 'employee@example.com',
                ],
            ),
        ];

        $this->wildcardSource200Deep = [
            'users' => array_fill(
                0,
                200,
                [
                    'profile' => [
                        'contact' => [
                            'email' => 'user@example.com',
                        ],
                    ],
                ],
            ),
        ];

        $this->wildcardSource1200Deep = [
            'employees' => array_fill(
                0,
                1200,
                [
                    'profile' => [
                        'contact' => [
                            'email' => 'employee@example.com',
                        ],
                    ],
                ],
            ),
        ];
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Groups(['docs'])]
    public function benchProcessWildcardMapping200Deep(): void
    {
        $target = [];

        MappingEngine::processWildcardMapping(
            $this->wildcardValues200Deep,
            $target,
            'users.*.profile.contact.email',
            'users.*.profile.contact.email',
            $this->wildcardSource200Deep,
            0,
            true,
            false,
            [],
            null,
            null,
            null,
            true,
            false,
        );
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchProcessWildcardMapping1200Deep(): void
    {
        $target = [];

        MappingEngine::processWildcardMapping(
            $this->wildcardValues1200Deep,
            $target,
            'employees.*.profile.contact.email',
            'employees.*.profile.contact.email',
            $this->wildcardSource1200Deep,
            0,
            true,
            false,
            [],
            null,
            null,
            null,
            true,
            false,
        );
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchProcessWildcardMapping200(): void
    {
        $target = [];

        MappingEngine::processWildcardMapping(
            $this->wildcardValues200,
            $target,
            'users.*.email',
            'users.*.email',
            $this->wildcardSource200,
            0,
            true,
            false,
            [],
            null,
            null,
            null,
            true,
            false,
        );
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchProcessWildcardMapping1200(): void
    {
        $target = [];

        MappingEngine::processWildcardMapping(
            $this->wildcardValues1200,
            $target,
            'employees.*.email',
            'employees.*.email',
            $this->wildcardSource1200,
            0,
            true,
            false,
            [],
            null,
            null,
            null,
            true,
            false,
        );
    }
}
