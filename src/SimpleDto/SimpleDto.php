<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use JsonSerializable;

/**
 * Lightweight, high-performance Data Transfer Object.
 *
 * SimpleDto is designed for maximum performance (~3.0μs per operation)
 * with minimal overhead. It provides essential features:
 * - Property mapping with #[MapFrom] and #[MapTo]
 * - Serialization control with #[Hidden]
 * - Empty value handling with #[ConvertEmptyToNull]
 * - Optional Converter support with #[ConverterMode]
 * - Nested DTOs and Collections
 * - Additional features: diff(), with(), sorted(), wrap(), etc.
 * - Framework integrations: Doctrine, Eloquent
 *
 * Performance:
 * - Standard mode: ~3.0μs per operation
 * - ConverterMode: ~2-3μs (JSON, XML, CSV, etc.)
 *
 * Example usage:
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           #[MapFrom('user_name')]
 *           public readonly string $name,
 *           #[Hidden]
 *           public readonly string $password,
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['user_name' => 'John', 'password' => 'secret']);
 *   echo $user->name; // 'John'
 *   $array = $user->toArray(); // ['name' => 'John'] (password hidden)
 *
 * With ConverterMode:
 *   #[ConverterMode]
 *   class ApiDto extends SimpleDto {
 *       public function __construct(
 *           public readonly string $name,
 *       ) {}
 *   }
 *
 *   $dto = ApiDto::from('{"name": "John"}'); // JSON
 *   $dto = ApiDto::from('<root><name>John</name></root>'); // XML
 *
 * Additional features:
 *   $dto->diff(['name' => 'Jane']); // Compare with data
 *   $dto->with('extra', 'value'); // Add extra data
 *   $dto->sorted(); // Sort output keys
 *   $dto->wrap('data'); // Wrap in key
 */
abstract class SimpleDto implements DtoInterface, JsonSerializable
{
    use SimpleDtoTrait;
}
