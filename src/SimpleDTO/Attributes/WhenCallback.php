<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

/**
 * Conditional attribute: Include property based on a callback.
 *
 * Supports two syntaxes:
 * 1. Global function or static method with optional parameters
 * 2. Legacy: Direct callable (closures, invokable objects)
 *
 * Example with string reference (recommended for attributes):
 * ```php
 * // Global function
 * function isAdult(object $dto): bool {
 *     return $dto->age >= 18;
 * }
 *
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *
 *         // Global function
 *         #[WhenCallback('isAdult')]
 *         public readonly ?string $adultContent = null,
 *
 *         // Static method
 *         #[WhenCallback('static::checkPermission', ['admin'])]
 *         public readonly ?array $adminData = null,
 *
 *         // With named parameters
 *         #[WhenCallback('static::hasRole', ['role' => 'editor', 'strict' => true])]
 *         public readonly ?string $editorContent = null,
 *     ) {}
 *
 *     public static function checkPermission(object $dto, mixed $value, array $context, string $permission): bool
 *     {
 *         return in_array($permission, $context['permissions'] ?? []);
 *     }
 *
 *     public static function hasRole(object $dto, mixed $value, array $context, string $role, bool $strict = false): bool
 *     {
 *         return $strict
 *             ? ($context['role'] ?? null) === $role
 *             : in_array($role, $context['roles'] ?? []);
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenCallback implements ConditionalProperty
{
    /**
     * @param string|array|object $callback Function name, 'static::methodName', array callable, or invokable object
     * @param array<string|int, mixed> $parameters Parameters to pass to the callback (positional or named)
     */
    public function __construct(
        public readonly string|array|object $callback,
        public readonly array $parameters = [],
    ) {}

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $callback = $this->resolveCallback($dto);

        if (!is_callable($callback)) {
            return false;
        }

        // Build arguments: always start with ($dto, $value, $context)
        $args = [$dto, $value, $context];

        // Add additional parameters
        if ([] !== $this->parameters) {
            // Check if parameters are named (associative array)
            $isNamed = array_keys($this->parameters) !== range(0, count($this->parameters) - 1);

            if ($isNamed) {
                // Named parameters: merge with base args
                $args = array_merge($args, $this->parameters);
            } else {
                // Positional parameters: append to base args
                foreach ($this->parameters as $param) {
                    $args[] = $param;
                }
            }
        }

        // @phpstan-ignore argument.type (Callback signature is flexible)
        return (bool)$callback(...$args);
    }

    /**
     * Resolve the callback to a callable.
     *
     * @param object $dto The DTO instance
     * @return callable|null
     */
    private function resolveCallback(object $dto): mixed
    {
        // If string, resolve to function or static method
        if (is_string($this->callback)) {
            // Check for 'static::methodName' pattern
            if (str_starts_with($this->callback, 'static::')) {
                $method = substr($this->callback, 8); // Remove 'static::'
                $class = $dto::class;

                if (method_exists($class, $method)) {
                    return [$class, $method];
                }

                return null;
            }

            // Check for 'ClassName::methodName' pattern
            if (str_contains($this->callback, '::')) {
                $parts = explode('::', $this->callback, 2);
                if (2 === count($parts) && method_exists($parts[0], $parts[1])) {
                    return $parts;
                }

                return null;
            }

            // Check for global function
            if (function_exists($this->callback)) {
                return $this->callback;
            }

            return null;
        }

        // If already callable (legacy support for closures, invokables, array callables)
        if (is_callable($this->callback)) {
            return $this->callback;
        }

        return null;
    }
}
