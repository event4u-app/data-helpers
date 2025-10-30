<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;
use InvalidArgumentException;

/**
 * Attribute to specify automatic input name transformation for all properties.
 *
 * This is a class-level attribute that transforms all input keys to match
 * the Dto property names. Individual properties can override this with #[MapFrom].
 *
 * Supported formats:
 * - 'snake_case': user_name → userName
 * - 'camelCase': userName → userName (no change)
 * - 'kebab-case': user-name → userName
 * - 'PascalCase': UserName → userName
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\LiteDto\Attributes\MapInputName;
 * use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;
 *
 * #[MapInputName(NamingConvention::SnakeCase)]
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $userName,      // Accepts 'user_name' from input
 *         public readonly string $emailAddress,  // Accepts 'email_address' from input
 *     ) {}
 * }
 *
 * $dto = UserDto::from([
 *     'user_name' => 'John Doe',
 *     'email_address' => 'john@example.com',
 * ]);
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MapInputName
{
    public NamingConvention $convention;

    /** @param string|NamingConvention $format The naming format of input keys */
    public function __construct(
        string|NamingConvention $format,
    ) {
        $this->convention = is_string($format)
            ? (NamingConvention::fromString($format) ?? throw new InvalidArgumentException(
                'Invalid naming convention: ' . $format
            ))
            : $format;
    }

    /** Get the format string for backward compatibility. */
    public function getFormat(): string
    {
        return $this->convention->value;
    }
}
