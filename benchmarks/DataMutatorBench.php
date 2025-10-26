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
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleSet(): void
    {
        DataMutator::set($this->simpleData, 'name', 'Bob');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchNestedSet(): void
    {
        DataMutator::set($this->nestedData, 'user.profile.name', 'Bob');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchDeepSet(): void
    {
        DataMutator::set($this->nestedData, 'user.profile.address.city', 'Berlin');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMultipleSet(): void
    {
        DataMutator::set($this->nestedData, [
            'user.profile.name' => 'Bob',
            'user.profile.age' => 35,
        ]);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMerge(): void
    {
        DataMutator::merge($this->nestedData, 'user.profile');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchUnset(): void
    {
        DataMutator::unset($this->nestedData);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMultipleUnset(): void
    {
        DataMutator::unset($this->nestedData);
    }
}
