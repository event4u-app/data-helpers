---
title: LiteDto & SimpleDto
description: Comparison of LiteDto and SimpleDto - Choose the right DTO for your needs
---

Data Helpers provides two DTO implementations, each optimized for different use cases:

- **LiteDto** - Maximum performance with minimal overhead
- **SimpleDto** - Full-featured with validation, type casting and advanced features

## Quick Overview

### LiteDto

LiteDto is designed for **maximum performance** when you need fast data transfer without validation or type casting.

```php
use event4u\DataHelpers\LiteDto;

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
use event4u\DataHelpers\SimpleDto;
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

**Performance Tip:** Use `#[UltraFast]` attribute for 3-4x faster performance when validation is not needed:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;

#[UltraFast]  // Auto-detect attributes, skip validation (~2-3μs)
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[MapFrom('email_address')]  // Still works!
        public readonly string $email,

        #[Hidden]  // Still works!
        public readonly string $password,
    ) {}
}

// Ultra-fast creation: ~2-3μs (3-4x faster than normal SimpleDto!)
$user = UserDto::from([
    'name' => 'John',
    'email_address' => 'john@example.com',
    'password' => 'secret',
]);
```

## Performance & Feature Comparison

<!-- DTO_COMPARISON_START -->

| Feature | LiteDto #[UltraFast] | LiteDto | SimpleDto #[UltraFast] | SimpleDto |
|---------|----------------------|---------|------------------------|-----------|
| **Creation Performance** | ~1.2μs | ~2.7μs | ~4.7μs | ~5.2μs |
| **Creation Speed Factor** | **4.2x faster** | **1.9x faster** | **1.1x faster** | Baseline |
| **Serialization Performance** | ~1.4μs | ~4.0μs | ~26.0μs | ~26.4μs |
| **Serialization Speed Factor** | **18.4x faster** | **6.6x faster** | **1.0x faster** | Baseline |
| | | | | |
| **Core Features** | | | | |
| Property Mapping | ✅ | ✅ | ✅ | ✅ |
| Nested DTOs | ✅ | ✅ | ✅ | ✅ |
| Collections | ✅ | ✅ | ✅ | ✅ |
| Hidden Properties | ✅ | ✅ | ✅ | ✅ |
| Immutability | ✅ | ✅ | ✅ | ✅ |
| | | | | |
| **Validation** | | | | |
| Built-in Validation | ❌ | ❌ | ❌ | ✅ |
| Custom Validation | ❌ | ❌ | ❌ | ✅ |
| Validation Attributes | ❌ | ❌ | ❌ | ✅ |
| | | | | |
| **Type Casting** | | | | |
| Automatic Casting | ❌ | ❌ | ✅ | ✅ |
| DateTime Casting | ❌ | ❌ | ✅ | ✅ |
| Enum Casting | ❌ | ❌ | ✅ | ✅ |
| Custom Casts | ❌ | ❌ | ✅ | ✅ |
| | | | | |
| **Advanced Features** | | | | |
| Computed Properties | ❌ | ❌ | ❌ | ✅ |
| Lazy Properties | ❌ | ❌ | ❌ | ✅ |
| Conditional Properties | ❌ | ❌ | ❌ | ✅ |
| Hooks & Events | ❌ | ❌ | ❌ | ✅ |
| Dot Notation Access | ✅ | ✅ | ✅ | ✅ |
| | | | | |
| **Data Conversion** | | | | |
| Converter Support | ☑️ | ☑️ | ✅ | ✅ |
| ConvertEmptyToNull | ❌ | ✅ | ✅ | ✅ |
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
| #[ConverterMode] | ❌ | ✅ | ❌ | ✅ |
| #[AutoCast] | ❌ | ❌ | ❌ | ✅ |
| #[NoAttributes] | ❌ | ❌ | ❌ | ✅ |
| #[NoCasts] | ❌ | ❌ | ❌ | ✅ |
| #[NoValidation] | ❌ | ❌ | ❌ | ✅ |
| #[ValidateRequest] | ❌ | ❌ | ❌ | ✅ |
| | | | | |
| **Property Attributes** | | | | |
| #[MapFrom] | ✴️ | ✅ | ✴️ | ✅ |
| #[MapTo] | ✴️ | ✅ | ✴️ | ✅ |
| #[MapInputName] | ❌ | ❌ | ❌ | ✅ |
| #[MapOutputName] | ❌ | ❌ | ❌ | ✅ |
| #[Hidden] | ❌ | ✅ | ❌ | ✅ |
| #[HiddenFromArray] | ❌ | ❌ | ❌ | ✅ |
| #[HiddenFromJson] | ❌ | ❌ | ❌ | ✅ |
| #[Visible] | ❌ | ❌ | ❌ | ✅ |
| #[CastWith] | ✴️ | ✅ | ✴️ | ✅ |
| #[EnumSerialize] | ❌ | ✅ | ❌ | ✅ |
| #[ConvertEmptyToNull] | ❌ | ✅ | ❌ | ✅ |
| #[DataCollectionOf] | ❌ | ✅ | ❌ | ✅ |
| #[Computed] | ❌ | ❌ | ❌ | ✅ |
| #[Lazy] | ❌ | ❌ | ❌ | ✅ |
| #[Optional] | ❌ | ❌ | ❌ | ✅ |
| | | | | |
| **Validation Attributes** | | | | |
| #[Required] | ❌ | ❌ | ❌ | ✅ |
| #[RequiredIf] | ❌ | ❌ | ❌ | ✅ |
| #[RequiredUnless] | ❌ | ❌ | ❌ | ✅ |
| #[RequiredWith] | ❌ | ❌ | ❌ | ✅ |
| #[RequiredWithout] | ❌ | ❌ | ❌ | ✅ |
| #[Nullable] | ❌ | ❌ | ❌ | ✅ |
| #[Sometimes] | ❌ | ❌ | ❌ | ✅ |
| #[Email] | ❌ | ❌ | ❌ | ✅ |
| #[Url] | ❌ | ❌ | ❌ | ✅ |
| #[Uuid] | ❌ | ❌ | ❌ | ✅ |
| #[Ip] | ❌ | ❌ | ❌ | ✅ |
| #[Json] | ❌ | ❌ | ❌ | ✅ |
| #[Min] | ❌ | ❌ | ❌ | ✅ |
| #[Max] | ❌ | ❌ | ❌ | ✅ |
| #[Between] | ❌ | ❌ | ❌ | ✅ |
| #[Size] | ❌ | ❌ | ❌ | ✅ |
| #[In] | ❌ | ❌ | ❌ | ✅ |
| #[NotIn] | ❌ | ❌ | ❌ | ✅ |
| #[Regex] | ❌ | ❌ | ❌ | ✅ |
| #[StartsWith] | ❌ | ❌ | ❌ | ✅ |
| #[EndsWith] | ❌ | ❌ | ❌ | ✅ |
| #[Confirmed] | ❌ | ❌ | ❌ | ✅ |
| #[ConfirmedBy] | ❌ | ❌ | ❌ | ✅ |
| #[Same] | ❌ | ❌ | ❌ | ✅ |
| #[Different] | ❌ | ❌ | ❌ | ✅ |
| #[Unique] | ❌ | ❌ | ❌ | ✅ |
| #[Exists] | ❌ | ❌ | ❌ | ✅ |
| #[File] | ❌ | ❌ | ❌ | ✅ |
| #[Image] | ❌ | ❌ | ❌ | ✅ |
| #[Mimes] | ❌ | ❌ | ❌ | ✅ |
| #[MimeTypes] | ❌ | ❌ | ❌ | ✅ |
| | | | | |
| **Conditional Attributes** | | | | |
| #[WhenCallback] | ❌ | ❌ | ❌ | ✅ |
| #[WhenValue] | ❌ | ❌ | ❌ | ✅ |
| #[WhenEquals] | ❌ | ❌ | ❌ | ✅ |
| #[WhenIn] | ❌ | ❌ | ❌ | ✅ |
| #[WhenTrue] | ❌ | ❌ | ❌ | ✅ |
| #[WhenFalse] | ❌ | ❌ | ❌ | ✅ |
| #[WhenNull] | ❌ | ❌ | ❌ | ✅ |
| #[WhenNotNull] | ❌ | ❌ | ❌ | ✅ |
| #[WhenInstanceOf] | ❌ | ❌ | ❌ | ✅ |
| #[WhenContext] | ❌ | ❌ | ❌ | ✅ |
| #[WhenContextEquals] | ❌ | ❌ | ❌ | ✅ |
| #[WhenContextIn] | ❌ | ❌ | ❌ | ✅ |
| #[WhenContextNotNull] | ❌ | ❌ | ❌ | ✅ |
| #[WhenAuth] (Laravel) | ❌ | ❌ | ❌ | ✅ |
| #[WhenGuest] (Laravel) | ❌ | ❌ | ❌ | ✅ |
| #[WhenCan] (Laravel) | ❌ | ❌ | ❌ | ✅ |
| #[WhenRole] (Laravel) | ❌ | ❌ | ❌ | ✅ |
| #[WhenGranted] (Symfony) | ❌ | ❌ | ❌ | ✅ |
| #[WhenSymfonyRole] (Symfony) | ❌ | ❌ | ❌ | ✅ |
| | | | | |
| **Other Attributes** | | | | |
| #[RuleGroup] | ❌ | ❌ | ❌ | ✅ |
| #[WithMessage] | ❌ | ❌ | ❌ | ✅ |
| | | | | |


**Legend:**

- ✅ Fully supported
- ☑️ Partially supported or optional
-✴️ Can be re-enabled with UltraFast parameters (e.g., `#[UltraFast(allowMapFrom: true)]`)
- ❌ Not supported

**Performance Notes:**

- **#[UltraFast]**: Available for both LiteDto and SimpleDto
  - LiteDto: Can re-enable specific attributes (e.g., `#[UltraFast(allowMapFrom: true)]`)
  - SimpleDto: Auto-detects which attributes are used and processes only those
- **#[ConverterMode]**: Available for LiteDto (normal mode) and SimpleDto
- **#[AutoCast]**: Enables automatic type casting for native PHP types (SimpleDto only)
- **#[NoAttributes]**: Skips ALL attribute processing for maximum performance (SimpleDto only)
- **#[NoCasts]**: Disables all type casting (SimpleDto only)
- **#[NoValidation]**: Skips ALL validation for trusted data sources (SimpleDto only)
- **#[ValidateRequest]**: Enables automatic request validation in Laravel/Symfony (SimpleDto only)
- **#[Optional]**: Zero overhead when not used (opt-in via feature flag, SimpleDto only)

## Detailed Comparison

### Performance

**LiteDto** is optimized for speed:
- ~4.5μs average creation time (normal mode)
- ~2.0μs with `#[UltraFast]` (**3.8x faster** than SimpleDto)
- Minimal reflection overhead
- No validation or casting overhead
- Use `#[UltraFast]` attribute for even better performance

**SimpleDto** provides full features:
- ~7.6μs average creation time (normal mode)
- ~7.7μs with `#[UltraFast]` (**1.0x faster**)
- Includes validation and type casting
- Rich attribute system
- More overhead but more features
- Use `#[UltraFast]` attribute to skip validation/casting when not needed

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

### Performance Tips

#### LiteDto Optimization

- Avoid `#[ConverterMode]` when only using arrays (~0.5μs overhead)
- Use `readonly` properties (required)
- Minimize nested DTOs
- Minimize attribute usage
- Use `#[UltraFast]` for even better performance (~2.0μs)

#### SimpleDto Optimization

- Use `#[UltraFast]` mode when validation is not needed (~7.7μs)
- Enable caching for repeated use
- Minimize validation rules
- Use computed properties sparingly

## Migration Between DTOs

### From SimpleDto to LiteDto

If you need better performance and don't need validation:

```php
// Before: SimpleDto
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
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
    public function __construct(
        public readonly string $name,

        #[Email]
        public readonly string $email,

        #[Min(18)]
        public readonly int $age,
    ) {}
}
```

## Performance Tips

### LiteDto Optimization

- Avoid `#[ConverterMode]` when only using arrays (~0.5μs overhead)
- Use `readonly` properties (required)
- Minimize nested DTOs
- Minimize attribute usage

### SimpleDto Optimization

- Use `#[UltraFast]` mode when validation is not needed (~2-3μs, **3-4x faster**)
- UltraFast auto-detects which attributes are used (zero overhead for unused features)
- Enable caching for repeated use
- Minimize validation rules
- Use computed properties sparingly
- Combine `#[UltraFast]` with `#[ConverterMode]` for JSON/XML support

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

