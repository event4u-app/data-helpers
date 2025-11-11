<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

/**
 * Dummy constraint used when Symfony Validator is not installed.
 *
 * This allows validation attributes to implement SymfonyConstraint interface
 * without requiring Symfony as a hard dependency.
 */
class DummyConstraint
{
    public function __construct(
        public readonly string $attributeName,
        public readonly ?string $message = null
    ) {}
}
