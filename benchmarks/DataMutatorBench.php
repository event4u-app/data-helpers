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
}
