<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\Support\CallbackHelper;
use InvalidArgumentException;

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
 * class UserDto extends SimpleDto
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
     * @param string|array<string>|object $callback Function name, 'static::methodName', array callable, or invokable object
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
     * @param object $dto The Dto instance
     * @param array<string, mixed> $context Additional context
     */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        try {
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

            // CallbackHelper will resolve 'static::method' automatically
            $result = CallbackHelper::execute($this->callback, ...$args);
            return (bool)$result;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
