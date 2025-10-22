<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Attribute to specify automatic output name transformation for all properties.
 *
 * This is a class-level attribute that transforms all DTO property names to
 * the specified format in the output. Individual properties can override this with #[MapTo].
 *
 * Supported formats:
 * - 'snake_case': userName → user_name
 * - 'camelCase': userName → userName (no change)
 * - 'kebab-case': userName → user-name
 * - 'PascalCase': userName → UserName
 *
 * Example:
 *   #[MapOutputName('snake_case')]
 *   class UserDTO extends SimpleDTO {
 *       public function __construct(
 *           public readonly string $userName,      // Outputs as 'user_name'
 *           public readonly string $emailAddress,  // Outputs as 'email_address'
 *       ) {}
 *   }
 *
 *   $dto = new UserDTO('John Doe', 'john@example.com');
 *   $array = $dto->toArray();
 *   // ['user_name' => 'John Doe', 'email_address' => 'john@example.com']
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MapOutputName
{
    /** @param string $format The naming format for output keys: 'snake_case', 'camelCase', 'kebab-case', 'PascalCase' */
    public function __construct(
        public readonly string $format,
    ) {
    }
}
