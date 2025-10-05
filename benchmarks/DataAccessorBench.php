<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataAccessor;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class DataAccessorBench
{
    /** @var array<string, mixed> */
    private array $simpleData;
    /** @var array<string, mixed> */
    private array $nestedData;
    /** @var array<string, mixed> */
    private array $deeplyNestedData;
    private DataAccessor $simpleAccessor;
    private DataAccessor $nestedAccessor;
    private DataAccessor $deepAccessor;

    public function setUp(): void
    {
        // Simple data
        $this->simpleData = [
            'name' => 'Alice',
            'age' => 30,
            'email' => 'alice@example.com',
        ];

        // Nested data
        $this->nestedData = [
            'user' => [
                'profile' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
                'emails' => [
                    ['type' => 'work', 'value' => 'alice@work.com'],
                    ['type' => 'home', 'value' => 'alice@home.com'],
                ],
            ],
        ];

        // Deeply nested data with wildcards
        $this->deeplyNestedData = [
            'departments' => array_fill(0, 10, [
                'name' => 'Engineering',
                'employees' => array_fill(0, 20, [
                    'name' => 'Employee',
                    'email' => 'employee@example.com',
                    'profile' => [
                        'age' => 30,
                        'city' => 'Berlin',
                    ],
                ]),
            ]),
        ];

        $this->simpleAccessor = new DataAccessor($this->simpleData);
        $this->nestedAccessor = new DataAccessor($this->nestedData);
        $this->deepAccessor = new DataAccessor($this->deeplyNestedData);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleGet(): void
    {
        $this->simpleAccessor->get('name');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchNestedGet(): void
    {
        $this->nestedAccessor->get('user.profile.name');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchWildcardGet(): void
    {
        $this->nestedAccessor->get('user.emails.*.value');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDeepWildcardGet(): void
    {
        $this->deepAccessor->get('departments.*.employees.*.email');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchTypedGetString(): void
    {
        $this->simpleAccessor->getString('name');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchTypedGetInt(): void
    {
        $this->simpleAccessor->getInt('age');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchCreateAccessor(): void
    {
        new DataAccessor($this->simpleData);
    }
}
