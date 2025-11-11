<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Concerns;

use event4u\DataHelpers\SimpleDto\Support\DummyConstraint;

/**
 * Trait for attributes that optionally support Symfony Validator.
 *
 * This trait allows validation attributes to work with or without Symfony Validator.
 * When Symfony is available, real constraints are returned.
 * When Symfony is not available, DummyConstraint objects are returned.
 */
trait OptionalSymfonyConstraint
{
    /** Check if Symfony Validator is installed. */
    protected function isSymfonyValidatorAvailable(): bool
    {
        return class_exists('Symfony\Component\Validator\Constraint');
    }

    /**
     * Create a Symfony constraint if available, otherwise return DummyConstraint.
     *
     * @param string $constraintClass Fully qualified class name of the Symfony constraint (as string!)
     * @param array<string, mixed> $options Named arguments for the constraint constructor
     * @return object Symfony Constraint or DummyConstraint
     */
    protected function createConstraint(string $constraintClass, array $options = []): object
    {
        if ($this->isSymfonyValidatorAvailable() && class_exists($constraintClass)) {
            return new $constraintClass(...$options);
        }

        return new DummyConstraint(
            attributeName: static::class,
            message: $this->extractMessage($options)
        );
    }

    /**
     * Create multiple Symfony constraints if available, otherwise return array with DummyConstraint.
     *
     * @param array<array{class: string, options: array<string, mixed>}> $constraints Array of constraint definitions (class as string!)
     * @return array<object> Array of Symfony Constraints or array with DummyConstraint
     */
    protected function createConstraints(array $constraints): array
    {
        if (!$this->isSymfonyValidatorAvailable()) {
            return [
                new DummyConstraint(
                    attributeName: static::class,
                    message: $this->extractMessage([])
                ),
            ];
        }

        $result = [];
        foreach ($constraints as $constraint) {
            $class = $constraint['class'];
            $options = $constraint['options'] ?? [];

            if (class_exists($class)) {
                $result[] = new $class(...$options);
            }
        }

        return $result;
    }

    /**
     * Extract message from options or from $this->message property.
     *
     * @param array<string, mixed> $options
     */
    private function extractMessage(array $options): ?string
    {
        $message = $options['message'] ?? null;

        // @phpstan-ignore-next-line function.alreadyNarrowedType (Generic trait used by multiple classes)
        if (null === $message && property_exists($this, 'message')) {
            $messageValue = $this->message;
            // @phpstan-ignore-next-line booleanOr.alwaysTrue, identical.alwaysTrue (Generic trait used by multiple classes)
            if (is_string($messageValue) || null === $messageValue) {
                return $messageValue;
            }
        }

        return is_string($message) ? $message : null;
    }
}
