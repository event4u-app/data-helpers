<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Concerns;

use RuntimeException;

/**
 * Trait for attributes that require Symfony Validator to be installed.
 *
 * Provides helper methods to check if Symfony Validator is available
 * and throw meaningful exceptions when it's not.
 */
trait RequiresSymfonyValidator
{
    /**
     * Check if Symfony Validator is installed.
     */
    protected function isSymfonyValidatorAvailable(): bool
    {
        return class_exists('Symfony\Component\Validator\Constraint');
    }

    /**
     * Ensure Symfony Validator is installed, throw exception if not.
     *
     * @throws RuntimeException
     */
    protected function ensureSymfonyValidatorAvailable(): void
    {
        if (!$this->isSymfonyValidatorAvailable()) {
            throw new RuntimeException(
                'Symfony Validator is not installed. ' .
                'Install it with: composer require symfony/validator'
            );
        }
    }
}

