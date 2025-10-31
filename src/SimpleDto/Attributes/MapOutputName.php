<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;
use InvalidArgumentException;

/**
 * Attribute to specify automatic output name transformation for all properties.
 *
 * This is a class-level attribute that transforms all Dto property names to
 * the specified format in the output. Individual properties can override this with #[MapTo].
 *
 * Supported formats:
 * - 'snake_case': userName → user_name
 * - 'camelCase': userName → userName (no change)
 * - 'kebab-case': userName → user-name
 * - 'PascalCase': userName → UserName
 *
 * Example:
 *   // Using enum (recommended)
 *   #[MapOutputName(NamingConvention::SnakeCase)]
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           public readonly string $userName,      // Outputs as 'user_name'
 *           public readonly string $emailAddress,  // Outputs as 'email_address'
 *       ) {}
 *   }
 *
 *   // Using string (backward compatible)
 *   #[MapOutputName('snake_case')]
 *   class UserDto extends SimpleDto { ... }
 *
 *   $dto = new UserDto('John Doe', 'john@example.com');
 *   $array = $dto->toArray();
 *   // ['user_name' => 'John Doe', 'email_address' => 'john@example.com']
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MapOutputName
{
    public readonly NamingConvention $convention;

    /** @param string|NamingConvention $format The naming format for output keys */
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
