<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\DataMapper;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class DataMapperBench
{
    /** @var array<string, mixed> */
    private array $simpleSource;
    /** @var array<string, mixed> */
    private array $nestedSource;
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

        $this->simpleMapping = [
            'name' => 'firstName',
            'surname' => 'lastName',
            'mail' => 'email',
        ];
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleMapping(): void
    {
        DataMapper::source($this->simpleSource)
            ->target([])
            ->template($this->simpleMapping)
            ->map();
    }

    #[Revs(1000)]
    #[Iterations(5)]
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
    public function benchAutoMap(): void
    {
        $target = ['firstName' => null, 'lastName' => null, 'email' => null];
        DataMapper::source($this->simpleSource)
            ->target($target)
            ->autoMap();
    }

    #[Revs(1000)]
    #[Iterations(5)]
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
}
