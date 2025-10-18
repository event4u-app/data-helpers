<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

final readonly class ConvertToNull implements FilterInterface
{
    /** @param array<int, mixed> $convertValues */
    public function __construct(private array $convertValues = ['', 'N/A', 'null', 'NULL']) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        return in_array($value, $this->convertValues, true) ? null : $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return [];
    }
}
