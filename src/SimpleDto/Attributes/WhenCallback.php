<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use Closure;
use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenCallback as ConditionalWhenCallback;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use ReflectionFunction;

/**
 * Conditional attribute: Include property based on a callback.
 *
 * This is an alias for the Conditional\WhenCallback attribute for backward compatibility.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *
 *         #[WhenCallback(fn($value, $dto) => $dto->age >= 18)]
 *         public readonly ?string $adultContent = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class WhenCallback implements ConditionalProperty
{
    private readonly ConditionalWhenCallback $inner;

    /**
     * @param callable(mixed, object, array<string, mixed>): bool|string $callback Callback that receives ($value, $dto, $context) and returns bool.
     *                                  Can be a closure, callable array, or string reference to function/method.
     * @param array<string, mixed> $parameters Optional parameters to pass to the callback
     */
    public function __construct(
        public readonly mixed $callback,
        public readonly array $parameters = [],
    ) {
        // Wrap the callback to handle string references and parameters
        $wrappedCallback = $this->wrapCallback($callback, $parameters);
        $this->inner = new ConditionalWhenCallback($wrappedCallback);
    }

    /**
     * Wrap the callback to handle string references and parameters.
     *
     * @param callable(mixed, object, array<string, mixed>): bool|string $callback
     * @param array<string, mixed> $parameters
     * @return callable(mixed, object, array<string, mixed>): bool
     */
    private function wrapCallback(mixed $callback, array $parameters): callable
    {
        // If it's already a closure or callable, wrap it to inject parameters
        if (is_callable($callback) && !is_string($callback)) {
            if ([] === $parameters) {
                // No parameters - just pass through with correct argument order
                return function(mixed $value, object $dto, array $context = []) use ($callback): bool {
                    // Call with correct order: ($dto, $value, $context) or legacy ($dto) or ($value, $dto)
                    // Try to detect signature by reflection
                    $reflection = new ReflectionFunction($callback instanceof Closure ? $callback : $callback(...));
                    $params = $reflection->getParameters();

                    // Legacy signature: fn($dto) or fn($dto, $value) or fn($value, $dto)
                    if (count($params) === 1) {
                        // Single parameter - assume it's $dto
                        return (bool)$callback($dto); // @phpstan-ignore-line arguments.count
                    }

                    if (count($params) === 2) {
                        // Two parameters - check first parameter name
                        $firstParam = $params[0]->getName();
                        if ('dto' === $firstParam || 'object' === $firstParam) {
                            // ($dto, $value)
                            return (bool)$callback($dto, $value); // @phpstan-ignore-line arguments.count
                        }

                        // ($value, $dto) - legacy
                        return (bool)$callback($value, $dto); // @phpstan-ignore-line arguments.count
                    }

                    // Three or more parameters - assume new signature ($dto, $value, $context)
                    return (bool)$callback($dto, $value, $context); // @phpstan-ignore-line argument.type
                };
            }

            return fn(mixed $value, object $dto, array $context = []): bool =>
                // Merge parameters with the standard arguments
                (bool)$callback($dto, $value, $context, ...$parameters); // @phpstan-ignore-line argument.type
        }

        // Handle string references (function names, static methods, etc.)
        if (is_string($callback)) { // @phpstan-ignore-line function.alreadyNarrowedType
            return function(mixed $value, object $dto, array $context = []) use ($callback, $parameters): bool {
                // Try to resolve the callback
                $resolved = $this->resolveStringCallback($callback, $dto);

                if (!is_callable($resolved)) {
                    return false;
                }

                // Call with parameters
                if ([] !== $parameters) {
                    // Check if parameters are named or positional
                    $isNamed = array_keys($parameters) !== range(0, count($parameters) - 1);

                    // Positional parameters
                    return (bool)$resolved($dto, $value, $context, ...$parameters); // @phpstan-ignore-line argument.type
                }

                return (bool)$resolved($dto, $value, $context); // @phpstan-ignore-line argument.type
            };
        }

        // Fallback: return a callback that always returns false
        return fn(): bool => false;
    }

    /**
     * Resolve string callback to callable.
     * @return callable(mixed, object, array<string, mixed>): bool|null
     */
    private function resolveStringCallback(string $callback, object $dto): ?callable
    {
        // Check if it's a function
        if (function_exists($callback)) {
            return $callback;
        }

        // Check if it's a static method reference (Class::method or static::method)
        if (str_contains($callback, '::')) {
            [$class, $method] = explode('::', $callback, 2);

            // Handle 'static::method' - resolve to DTO class
            if ('static' === $class) {
                $class = $dto::class;
            }

            if (method_exists($class, $method)) {
                return [$class, $method]; // @phpstan-ignore-line return.type
            }
        }

        return null;
    }

    /**
     * Determine if the property should be included in serialization.
     *
     * @param mixed $value The property value
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        return $this->inner->shouldInclude($value, $dto, $context);
    }
}
