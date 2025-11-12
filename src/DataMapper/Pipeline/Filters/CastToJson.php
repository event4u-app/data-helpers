<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts values to JSON strings.
 *
 * Converts arrays and objects to JSON strings.
 * Skips null values and existing strings.
 *
 * Example:
 *   ['key' => 'value'] => '{"key":"value"}'
 *   object => '{"property":"value"}'
 *
 * Usage:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([CastToJson::class])->map()->getTarget();
 */
final class CastToJson implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Already a string - skip encoding
        if (is_string($value)) {
            return $value;
        }

        // Encode to JSON (including null)
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): string
    {
        return 'json';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['json'];
    }
}
