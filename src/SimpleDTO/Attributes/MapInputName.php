<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Attribute to specify automatic input name transformation for all properties.
 *
 * This is a class-level attribute that transforms all input keys to match
 * the DTO property names. Individual properties can override this with #[MapFrom].
 *
 * Supported formats:
 * - 'snake_case': user_name → userName
 * - 'camelCase': userName → userName (no change)
 * - 'kebab-case': user-name → userName
 * - 'PascalCase': UserName → userName
 *
 * Example:
 *   #[MapInputName('snake_case')]
 *   class UserDTO extends SimpleDTO {
 *       public function __construct(
 *           public readonly string $userName,      // Accepts 'user_name' from input
 *           public readonly string $emailAddress,  // Accepts 'email_address' from input
 *       ) {}
 *   }
 *
 *   $dto = UserDTO::fromArray([
 *       'user_name' => 'John Doe',
 *       'email_address' => 'john@example.com',
 *   ]);
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MapInputName
{
    /** @param string $format The naming format of input keys: 'snake_case', 'camelCase', 'kebab-case', 'PascalCase' */
    public function __construct(
        public readonly string $format,
    ) {
    }
}

