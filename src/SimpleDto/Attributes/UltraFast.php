<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Ultra-Fast Mode Performance Attribute
 *
 * Bypasses ALL SimpleDto overhead for maximum performance.
 * Target: <1μs per operation (similar to Carapace speed).
 *
 * When applied to a DTO class, this attribute:
 * - ✅ Skips ConstructorMetadata cache system
 * - ✅ Skips all pipeline steps (template, filters, mapping)
 * - ✅ Skips cast system (AutoCast, Cast attributes)
 * - ✅ Skips validation system
 * - ✅ Skips lazy/optional property wrapping
 * - ✅ Uses direct reflection + constructor call (like Carapace)
 * - ✅ Only processes explicitly defined attributes on-demand
 *
 * Trade-offs:
 * - ❌ No DataMapper integration
 * - ❌ No automatic type casting
 * - ❌ No validation
 * - ❌ No lazy loading
 * - ❌ No optional properties
 * - ❌ No computed properties
 * - ❌ No visibility control
 * - ✅ Still supports: #[MapFrom], #[MapTo], #[CastWith] (processed on-demand)
 *
 * Use this when:
 * - You need maximum performance (e.g., processing thousands of DTOs)
 * - You have simple DTOs without complex features
 * - You want speed comparable to Carapace but with SimpleDto API
 * - Your data is already validated and in correct format
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto;
 * use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
 * use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
 *
 * #[UltraFast]
 * class DepartmentDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly string $code,
 *         public readonly float $budget,
 *         public readonly int $employee_count,
 *
 *         #[MapFrom('manager_name')]  // Still works!
 *         public readonly string $manager,
 *     ) {}
 * }
 *
 * // Ultra-fast creation: ~0.5-1μs (30-50x faster than normal mode)
 * $dto = DepartmentDto::fromArray([
 *     'name' => 'Engineering',
 *     'code' => 'ENG',
 *     'budget' => 1000000.0,
 *     'employee_count' => 50,
 *     'manager_name' => 'John Doe',
 * ]);
 * ```
 *
 * Performance Comparison:
 * - Normal SimpleDto: ~13-17μs
 * - With #[NoAttributes, NoCasts, NoValidation]: ~8-10μs
 * - With #[UltraFast]: ~0.5-1μs (target)
 * - Carapace: ~0.3μs
 * - Plain PHP: ~0.12-0.14μs
 *
 * @see NoAttributes For disabling attribute processing only
 * @see NoCasts For disabling type casting only
 * @see NoValidation For disabling validation only
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UltraFast
{
    /**
     * Create a new UltraFast attribute.
     *
     * @param bool $allowMapFrom Whether to process #[MapFrom] attributes (default: true)
     * @param bool $allowMapTo Whether to process #[MapTo] attributes (default: true)
     * @param bool $allowCastWith Whether to process #[CastWith] attributes (default: false)
     */
    public function __construct(
        public bool $allowMapFrom = true,
        public bool $allowMapTo = true,
        public bool $allowCastWith = false,
    ) {}
}
