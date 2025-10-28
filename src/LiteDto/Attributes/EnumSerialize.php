<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Control how enums are serialized in toArray().
 *
 * Modes:
 * - 'value' (default): Serialize to enum value (->value)
 * - 'name': Serialize to enum name (->name)
 * - 'both': Serialize to ['name' => ..., 'value' => ...]
 *
 * Example:
 *   enum Status: string {
 *       case ACTIVE = 'active';
 *       case INACTIVE = 'inactive';
 *   }
 *
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *           #[EnumSerialize('value')]  // Default
 *           public readonly Status $status,
 *       ) {}
 *   }
 *
 *   $dto = UserDto::from(['name' => 'John', 'status' => 'active']);
 *   $dto->toArray(); // ['name' => 'John', 'status' => 'active']
 *
 * With 'name' mode:
 *   #[EnumSerialize('name')]
 *   public readonly Status $status;
 *   // toArray() => ['status' => 'ACTIVE']
 *
 * With 'both' mode:
 *   #[EnumSerialize('both')]
 *   public readonly Status $status;
 *   // toArray() => ['status' => ['name' => 'ACTIVE', 'value' => 'active']]
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EnumSerialize
{
    public function __construct(
        public readonly string $mode = 'value',
    ) {}
}
