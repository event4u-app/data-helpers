<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Enable Converter support for JSON, XML, CSV, etc.
 *
 * By default, SimpleDto only accepts arrays for maximum performance.
 * With #[ConverterMode], SimpleDto can parse JSON, XML, CSV, etc.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto;
 * use event4u\DataHelpers\SimpleDto\Attributes\ConverterMode;
 *
 * #[ConverterMode]
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *     ) {}
 * }
 *
 * // Now you can use JSON, XML, etc.
 * $dto = UserDto::fromArray('{"name": "John", "age": 30}');
 * $dto = UserDto::fromArray('<user><name>John</name><age>30</age></user>');
 * ```
 *
 * Can be combined with #[UltraFast] for fast JSON/XML parsing:
 * ```php
 * #[UltraFast]
 * #[ConverterMode]
 * class ApiDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *     ) {}
 * }
 *
 * // UltraFast + ConverterMode: ~1.3-1.5μs (vs ~2.5μs normal mode)
 * $dto = ApiDto::fromArray('{"name": "John"}');
 * ```
 *
 * Performance:
 * - Without ConverterMode: Only arrays accepted
 * - With ConverterMode: +~0.5μs overhead for format detection and parsing
 * - UltraFast + ConverterMode: ~1.3-1.5μs (still faster than normal mode)
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ConverterMode
{
}

