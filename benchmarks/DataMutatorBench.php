<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataMutator;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class DataMutatorBench
{
    /** @var array<string, mixed> */
    private array $simpleData;
    /** @var array<string, mixed> */
    private array $nestedData;
    /** @var array<string, mixed> */
    private array $wildcardData;
    /** @var array<string, mixed> */
    private array $deepWildcardData;

    public function setUp(): void
    {
        $this->simpleData = [
            'name' => 'Alice',
            'age' => 30,
        ];

        $this->nestedData = [
            'user' => [
                'profile' => [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ],
        ];

        $this->wildcardData = [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
                ['name' => 'Charlie', 'age' => 35],
            ],
        ];

        $this->deepWildcardData = [
            'companies' => [
                [
                    'name' => 'Company A',
                    'departments' => [
                        ['name' => 'IT', 'employees' => [['name' => 'Alice'], ['name' => 'Bob']]],
                        ['name' => 'HR', 'employees' => [['name' => 'Charlie'], ['name' => 'David']]],
                    ],
                ],
                [
                    'name' => 'Company B',
                    'departments' => [
                        ['name' => 'Sales', 'employees' => [['name' => 'Eve'], ['name' => 'Frank']]],
                        ['name' => 'Marketing', 'employees' => [['name' => 'Grace'], ['name' => 'Henry']]],
                    ],
                ],
            ],
        ];
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleSet(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->simpleData)->set('name', 'Bob');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchNestedSet(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->nestedData)->set('user.profile.name', 'Bob');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDeepSet(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->nestedData)->set('user.profile.address.city', 'Berlin');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMultipleSet(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->nestedData)->set([
            'user.profile.name' => 'Bob',
            'user.profile.age' => 35,
        ]);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMerge(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->nestedData)->merge('user.profile', ['city' => 'Berlin']);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchUnset(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->nestedData)->unset('user.profile.name');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMultipleUnset(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->nestedData)->unset(['user.profile.name', 'user.profile.age']);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchWildcardSet(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->wildcardData)->set('users.*.age', 40);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDeepWildcardSet(): void
    {
        /** @phpstan-ignore-next-line assign.propertyType */
        DataMutator::make($this->deepWildcardData)->set('companies.*.departments.*.employees.*.active', true);
    }
}
