<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Enable Converter support for JSON, XML, CSV, etc.
 *
 * By default, LiteDto only accepts arrays for maximum performance (~0.3μs).
 * With #[ConverterMode], LiteDto can parse JSON, XML, CSV, etc. (~2-3μs).
 *
 * Example:
 *   #[ConverterMode]
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *       ) {}
 *   }
 *
 *   // Now you can use JSON, XML, etc.
 *   $dto = UserDto::from('{"name": "John"}');
 *   $dto = UserDto::from('<user><name>John</name></user>');
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ConverterMode
{
}
