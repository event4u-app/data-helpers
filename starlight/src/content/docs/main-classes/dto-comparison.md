---
title: LiteDto & SimpleDto
description: Comparison of LiteDto and SimpleDto - Choose the right DTO for your needs
---

Data Helpers provides two DTO implementations, each optimized for different use cases:

- **LiteDto** - Maximum performance with minimal overhead
- **SimpleDto** - Full-featured with validation, type casting, and advanced features

## Quick Overview

### LiteDto

LiteDto is designed for **maximum performance** when you need fast data transfer without validation or type casting.

```php
use event4u\DataHelpers\LiteDto\LiteDto;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$user = UserDto::from(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
```

**Best for:**
- High-performance APIs
- Data transfer between layers
- Simple data structures
- When validation is handled elsewhere

### SimpleDto

SimpleDto provides **full validation and type casting** with a rich feature set.

```php
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\Attributes\Validation\Email;
use event4u\DataHelpers\Attributes\Validation\Min;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Email]
        public readonly string $email,

        #[Min(18)]
        public readonly int $age,
    ) {}
}

$user = UserDto::from(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
```

**Best for:**
- Form validation
- API input validation
- Complex data structures
- When you need type casting (DateTime, Enum, etc.)

## Performance & Feature Comparison

<!-- DTO_COMPARISON_START -->

| Feature | LiteDto #[UltraFast] | LiteDto | SimpleDto #[UltraFast] | SimpleDto |
|---------|----------------------|---------|------------------------|-----------|
| **Performance** | ~3.5μs | ~8.1μs | ~11.8μs | ~12.1μs |
| **Speed Factor** | **3.5x faster** | **1.5x faster** | **1.0x faster** | Baseline |
| | | | | |
| **Core Features** | | | | |
| Property Mapping | ✅ | ✅ | ✅ | ✅ |
| Nested DTOs | ✅ | ✅ | ✅ | ✅ |
| Collections | ✅ | ✅ | ✅ | ✅ |
| Hidden Properties | ✅ | ✅ | ✅ | ✅ |
| Immutability | ✅ | ✅ | ✅ | ✅ |
| | | | | |
| **Validation** | | | | |
| Built-in Validation | ✅ | ✅ | ❌ | ✅ |
| Custom Validation | ✅ | ✅ | ❌ | ✅ |
| Validation Attributes | ✅ | ✅ | ❌ | ✅ |
| | | | | |
| **Type Casting** | | | | |
| Automatic Casting | ✅ | ✅ | ✅ | ✅ |
| DateTime Casting | ✅ | ✅ | ✅ | ✅ |
| Enum Casting | ✅ | ✅ | ✅ | ✅ |
| Custom Casts | ✅ | ✅ | ✅ | ✅ |
| | | | | |
| **Advanced Features** | | | | |
| Computed Properties | ✅ | ✅ | ❌ | ✅ |
| Lazy Properties | ✅ | ✅ | ❌ | ✅ |
| Conditional Properties | ✅ | ✅ | ❌ | ✅ |
| Hooks & Events | ✅ | ✅ | ❌ | ✅ |
| Dot Notation Access | ✅ | ✅ | ✅ | ✅ |
| | | | | |
| **Data Conversion** | | | | |
| Converter Support | ☑️ | ☑️ | ✅ | ✅ |
| ConvertEmptyToNull | ✅ | ✅ | ✅ | ✅ |
| JSON/XML Support | ☑️ | ☑️ | ✅ | ✅ |
| | | | | |
| **Developer Experience** | | | | |
| IDE Autocomplete | ✅ | ✅ | ✅ | ✅ |
| TypeScript Generation | ✅ | ✅ | ✅ | ✅ |
| Constructor Promotion | ✅ | ✅ | ✅ | ✅ |
| Property Attributes | ☑️ | ☑️ | ☑️ | ✅ |

**Legend:**

- ✅ Fully supported
- ☑️ Partially supported or optional
- ❌ Not supported
<!-- DTO_COMPARISON_END -->

## Available Attributes

| Attribute | LiteDto #[UltraFast] | LiteDto | SimpleDto #[UltraFast] | SimpleDto |
|-----------|----------------------|---------|------------------------|-----------|
| **Class Attributes** | | | | |
| #[UltraFast] | ✅ | ✅ | ✅ | ✅ |
| #[ConverterMode] | ✅ | ✅ | ✅ | ✅ |
| #[AutoCast] | ✅ | ✅ | ❌ | ✅ |
| #[NoAttributes] | ✅ | ✅ | ❌ | ✅ |
| #[NoCasts] | ✅ | ✅ | ❌ | ✅ |
| #[NoValidation] | ✅ | ✅ | ❌ | ✅ |
| #[ValidateRequest] | ✅ | ✅ | ❌ | ✅ |
| #[NotImmutable] | ✅ | ✅ | ❌ | ✅ |
| | | | | |
| **Property Attributes** | | | | |
| #[MapFrom] | ✅ | ✅ | ✅ | ✅ |
| #[MapTo] | ✅ | ✅ | ✅ | ✅ |
| #[MapInputName] | ✅ | ✅ | ✅ | ✅ |
| #[MapOutputName] | ✅ | ✅ | ✅ | ✅ |
| #[Hidden] | ✅ | ✅ | ✅ | ✅ |
| #[HiddenFromArray] | ✅ | ✅ | ✅ | ✅ |
| #[HiddenFromJson] | ✅ | ✅ | ✅ | ✅ |
| #[Visible] | ✅ | ✅ | ✅ | ✅ |
| #[CastWith] | ✅ | ✅ | ✅ | ✅ |
| #[EnumSerialize] | ✅ | ✅ | ✅ | ✅ |
| #[ConvertEmptyToNull] | ✅ | ✅ | ✅ | ✅ |
| #[DataCollectionOf] | ✅ | ✅ | ✅ | ✅ |
| #[Computed] | ✅ | ✅ | ✅ | ✅ |
| #[Lazy] | ✅ | ✅ | ✅ | ✅ |
| #[Optional] | ✅ | ✅ | ❌ | ✅ |
| #[NotImmutable] | ✅ | ✅ | ❌ | ✅ |
| | | | | |
| **Validation Attributes** | | | | |
| #[Required] | ✅ | ✅ | ❌ | ✅ |
| #[RequiredIf] | ✅ | ✅ | ❌ | ✅ |
| #[RequiredUnless] | ✅ | ✅ | ❌ | ✅ |
| #[RequiredWith] | ✅ | ✅ | ❌ | ✅ |
| #[RequiredWithout] | ✅ | ✅ | ❌ | ✅ |
| #[Nullable] | ✅ | ✅ | ❌ | ✅ |
| #[Sometimes] | ✅ | ✅ | ❌ | ✅ |
| #[Email] | ✅ | ✅ | ❌ | ✅ |
| #[Url] | ✅ | ✅ | ❌ | ✅ |
| #[Uuid] | ✅ | ✅ | ❌ | ✅ |
| #[Ip] | ✅ | ✅ | ❌ | ✅ |
| #[Json] | ✅ | ✅ | ❌ | ✅ |
| #[Min] | ✅ | ✅ | ❌ | ✅ |
| #[Max] | ✅ | ✅ | ❌ | ✅ |
| #[Between] | ✅ | ✅ | ❌ | ✅ |
| #[Size] | ✅ | ✅ | ❌ | ✅ |
| #[In] | ✅ | ✅ | ❌ | ✅ |
| #[NotIn] | ✅ | ✅ | ❌ | ✅ |
| #[Regex] | ✅ | ✅ | ❌ | ✅ |
| #[StartsWith] | ✅ | ✅ | ❌ | ✅ |
| #[EndsWith] | ✅ | ✅ | ❌ | ✅ |
| #[Confirmed] | ✅ | ✅ | ❌ | ✅ |
| #[ConfirmedBy] | ✅ | ✅ | ❌ | ✅ |
| #[Same] | ✅ | ✅ | ❌ | ✅ |
| #[Different] | ✅ | ✅ | ❌ | ✅ |
| #[Unique] | ☑️ | ☑️ | ❌ | ✅ |
| #[UniqueCallback] | ✅ | ✅ | ❌ | ❌ |
| #[Exists] | ☑️ | ☑️ | ❌ | ✅ |
| #[ExistsCallback] | ✅ | ✅ | ❌ | ❌ |
| #[File] | ☑️ | ☑️ | ❌ | ✅ |
| #[FileCallback] | ✅ | ✅ | ❌ | ❌ |
| #[Image] | ☑️ | ☑️ | ❌ | ✅ |
| #[Mimes] | ☑️ | ☑️ | ❌ | ✅ |
| #[MimeTypes] | ☑️ | ☑️ | ❌ | ✅ |
| | | | | |
| **Conditional Attributes** | | | | |
| #[WhenCallback] | ✅ | ✅ | ❌ | ✅ |
| #[WhenValue] | ✅ | ✅ | ❌ | ✅ |
| #[WhenEquals] | ✅ | ✅ | ❌ | ✅ |
| #[WhenIn] | ✅ | ✅ | ❌ | ✅ |
| #[WhenTrue] | ✅ | ✅ | ❌ | ✅ |
| #[WhenFalse] | ✅ | ✅ | ❌ | ✅ |
| #[WhenNull] | ✅ | ✅ | ❌ | ✅ |
| #[WhenNotNull] | ✅ | ✅ | ❌ | ✅ |
| #[WhenInstanceOf] | ✅ | ✅ | ❌ | ✅ |
| #[WhenContext] | ✅ | ✅ | ❌ | ✅ |
| #[WhenContextEquals] | ✅ | ✅ | ❌ | ✅ |
| #[WhenContextIn] | ✅ | ✅ | ❌ | ✅ |
| #[WhenContextNotNull] | ✅ | ✅ | ❌ | ✅ |
| #[WhenAuth] (Laravel) | ✅ | ✅ | ❌ | ✅ |
| #[WhenGuest] (Laravel) | ✅ | ✅ | ❌ | ✅ |
| #[WhenCan] (Laravel) | ✅ | ✅ | ❌ | ✅ |
| #[WhenRole] (Laravel) | ✅ | ✅ | ❌ | ✅ |
| #[WhenGranted] (Symfony) | ✅ | ✅ | ❌ | ✅ |
| #[WhenSymfonyRole] (Symfony) | ✅ | ✅ | ❌ | ✅ |
| | | | | |
| **Other Attributes** | | | | |
| #[RuleGroup] | ✅ | ✅ | ❌ | ✅ |
| #[WithMessage] | ✅ | ✅ | ❌ | ✅ |
| | | | | |


**Legend:**

- ✅ Fully supported
- ☑️ Partially supported or optional
- ❌ Not supported

**Performance Notes:**

- **#[Optional]**: Zero overhead when not used (opt-in via feature flag)
- **#[NoAttributes]**: Skips ALL attribute processing for maximum performance (LiteDto only)
- **#[NoValidation]**: Skips ALL validation for trusted data sources (LiteDto only)
- **#[ValidateRequest]**: Enables automatic request validation in Laravel/Symfony (LiteDto only)
- **#[AutoCast]**: Enables automatic type casting for native PHP types (SimpleDto only)
- **#[NoCasts]**: Disables all type casting (SimpleDto only)
- **Conditional Validation** (#[RequiredIf], #[RequiredUnless], #[RequiredWith], #[RequiredWithout]): Zero overhead when not used (opt-in via feature flag)
- **Conditional Properties** (#[WhenCallback], #[WhenValue], #[WhenEquals], etc.): Zero overhead when not used (opt-in via feature flag)
- **#[RuleGroup]**: Zero overhead when not used (opt-in via feature flag)
- **#[WithMessage]**: Zero overhead when not used (opt-in via feature flag)
- **All LiteDto attributes** use feature-flag system for optimal performance

## Detailed Comparison

### Performance

**LiteDto** is optimized for speed:
- ~4.4μs average operation time
- **5.3x faster** than SimpleDto
- Minimal reflection overhead
- No validation or casting overhead
- **Performance Attributes** for even more control:
  - `#[NoAttributes]` - Skip ALL attribute processing (maximum speed)
  - `#[NoValidation]` - Skip validation for trusted data sources

**SimpleDto** provides full features:
- ~12.1μs average operation time
- Includes validation and type casting
- Rich attribute system
- More overhead but more features
- Use `#[UltraFast]` attribute to skip validation/casting when not needed (~11.8μs, **1.0x faster**)
- **On-Demand Validation**: Create DTOs fast, validate later when needed (best of both worlds!)
- **Performance Attributes** for optimization:
  - `#[NoAttributes]` - Skip ALL attribute processing
  - `#[NoCasts]` - Skip type casting
  - `#[NoValidation]` - Skip validation
  - `#[AutoCast]` - Enable automatic type casting only when needed

### Use Cases

#### Choose LiteDto When:

✅ **Performance is critical**
- High-throughput APIs (1000+ requests/second)
- Real-time data processing
- Microservices communication
- Data transfer between layers

✅ **Simple data structures**
- No validation needed
- No type casting required
- Straightforward mapping

✅ **Validation handled elsewhere**
- Form validation in frontend
- API gateway validation
- Database constraints

#### Choose SimpleDto When:

✅ **Validation is required**
- Form input validation
- API request validation
- Business rule enforcement
- **Tip**: Use on-demand validation for best performance (create fast, validate when needed)

✅ **Type casting needed**
- DateTime conversion
- Enum mapping
- Custom type transformations

✅ **Advanced features required**
- Conditional properties
- Hooks and events
- Dot notation access

✅ **Complex data structures**
- Nested validation
- Collection validation
- Cross-field validation

### Performance Optimization Attributes

Both LiteDto and SimpleDto support performance optimization attributes to fine-tune behavior:

#### #[NoAttributes]

Skip **ALL** attribute processing for maximum performance:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\NoAttributes;

#[NoAttributes]  // Skip ALL attributes - maximum speed!
class FastDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// No MapFrom, no Hidden, no Validation - pure speed!
$dto = FastDto::from(['name' => 'John', 'email' => 'john@example.com']);
```

**When to use:**
- ✅ Simple data transfer objects
- ✅ High-throughput scenarios (10,000+ ops/sec)
- ✅ No attributes needed at all
- ✅ Maximum performance required

#### #[NoValidation]

Skip validation but keep other attributes:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\NoValidation;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;

#[NoValidation]  // Skip validation - data is already validated
class TrustedDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// MapFrom works, but no validation overhead
$dto = TrustedDto::from(['user_name' => 'John', 'email' => 'john@example.com']);
```

**When to use:**
- ✅ Data from trusted sources (database, internal APIs)
- ✅ Pre-validated data (validated by frontend/gateway)
- ✅ Need other attributes (MapFrom, Hidden, etc.) but not validation
- ✅ Performance-critical paths with trusted data

#### #[ValidateRequest]

Enable automatic request validation in Laravel/Symfony:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\ValidateRequest;
use event4u\DataHelpers\Attributes\Validation\Email;
use event4u\DataHelpers\Attributes\Validation\Required;

#[ValidateRequest(throw: true, stopOnFirstFailure: false)]
class CreateUserDto extends LiteDto
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required]
        #[Email]
        public readonly string $email,
    ) {}
}

// In Laravel Controller - automatic validation!
public function store(CreateUserDto $dto)
{
    // $dto is already validated - no need to call validate()
    User::create($dto->toArray());
}
```

**When to use:**
- ✅ Laravel/Symfony controllers
- ✅ Automatic request validation
- ✅ Clean controller code
- ✅ Framework integration

#### #[AutoCast]

Enable automatic type casting for native PHP types:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\AutoCast;

// Class-level: Enable AutoCast for ALL properties
#[AutoCast]
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,        // Automatically cast to int
        public readonly bool $active,    // Automatically cast to bool
        public readonly float $score,    // Automatically cast to float
    ) {}
}

// Strings are automatically cast to correct types
$dto = UserDto::from(['name' => 'John', 'age' => '30', 'active' => '1', 'score' => '9.5']);
// $dto->age === 30 (int)
// $dto->active === true (bool)
// $dto->score === 9.5 (float)

// Property-level: Enable AutoCast for specific properties only
class ProductDto extends LiteDto
{
    public function __construct(
        #[AutoCast]
        public readonly int $id,         // Only this property is auto-casted

        public readonly string $name,    // No auto-casting
    ) {}
}
```

**Supported types:** `int`, `float`, `string`, `bool`, `array`

**When to use:**
- ✅ Working with external APIs that return strings
- ✅ Processing form data (all values are strings)
- ✅ CSV/JSON imports with inconsistent types
- ✅ Need automatic type coercion

**Performance:** Zero overhead when values are already correct type!

#### #[NoCasts]

Disable **ALL** type casting for maximum performance:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\NoCasts;

#[NoCasts]  // Disable ALL casting - even #[CastWith], nested DTOs, enums!
class RawDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

// No casting happens - values must be correct type!
$dto = RawDto::from(['id' => 123, 'name' => 'John']);
```

**When to use:**
- ✅ Data is already in correct format (from database)
- ✅ Maximum performance required
- ✅ No type conversion needed at all
- ✅ Trusted data sources

**Note:** #[NoCasts] disables:
- ❌ #[CastWith] custom casters
- ❌ Nested DTO casting
- ❌ #[DataCollectionOf] collections
- ❌ Enum casting
- ❌ #[AutoCast] automatic casting

## Migration Between DTOs

### From SimpleDto to LiteDto

If you need better performance and don't need validation:

```php
// Before: SimpleDto
class UserDto extends SimpleDto
{
    public string $name;
    public string $email;
    public int $age;
}

// After: LiteDto
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}
```

### From LiteDto to SimpleDto

If you need validation or type casting:

```php
// Before: LiteDto
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// After: SimpleDto
class UserDto extends SimpleDto
{
    public string $name;

    #[Email]
    public string $email;

    #[Min(18)]
    public int $age;
}
```

## Performance Tips

### LiteDto Optimization

- Avoid `#[ConverterMode]` when only using arrays (~0.5μs overhead)
- Use `readonly` properties (required)
- Minimize nested DTOs
- Minimize attribute usage

### SimpleDto Optimization

- Use `#[UltraFast]` mode when validation is not needed (~3.7μs)
- Enable caching for repeated use
- Minimize validation rules
- Use computed properties sparingly

## Learn More

### LiteDto Documentation

- [LiteDto Introduction](/data-helpers/lite-dto/introduction/) - Getting started
- [Creating LiteDtos](/data-helpers/lite-dto/creating-litedtos/) - Best practices
- [LiteDto Attributes](/data-helpers/lite-dto/attributes/) - Available attributes
- [LiteDto Performance](/data-helpers/lite-dto/performance/) - Optimization tips

### SimpleDto Documentation

- [SimpleDto Introduction](/data-helpers/simple-dto/introduction/) - Getting started
- [Creating Dtos](/data-helpers/simple-dto/creating-dtos/) - Best practices
- [Validation](/data-helpers/simple-dto/validation/) - Validation rules
- [Type Casting](/data-helpers/simple-dto/type-casting/) - Type conversion
- [Performance Modes](/data-helpers/simple-dto/performance-modes/) - UltraFast mode

## Next Steps

- [Choose LiteDto for performance →](/data-helpers/lite-dto/introduction/)
- [Choose SimpleDto for validation →](/data-helpers/simple-dto/introduction/)
- [Compare all performance benchmarks →](/data-helpers/performance/benchmarks/)

