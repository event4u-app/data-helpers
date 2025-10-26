<?php

declare(strict_types=1);

namespace Tests\utils\Docu;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Example pipeline filter that converts email addresses to lowercase.
 * Used in documentation examples.
 */
class LowercaseEmails implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Check if the target path contains 'email' and value is a string
        $targetPath = $context->tgtPath();
        if ($targetPath && str_contains(strtolower($targetPath), 'email') && is_string($value)) {
            return strtolower($value);
        }

        return $value;
    }

    public function getHook(): string
    {
        return 'afterTransform';
    }

    public function getFilter(): ?string
    {
        return 'tgt:email';
    }

    public function getAliases(): array
    {
        return ['lowercase'];
    }
}
