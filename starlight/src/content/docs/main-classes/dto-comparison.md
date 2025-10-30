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
|---------|----------------------|---------|-----------------------|-----------|
| **Performance** | ~0.7μs | ~3.5μs | ~3.2μs | ~18.4μs |
| **Speed Factor** | **26.0x faster** | **5.3x faster** | **5.8x faster** | Baseline |
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
| Converter Support | ✴️ | ☑️ | ✴️ | ✅ |
| ConvertEmptyToNull | ✅ | ✅ | ✅ | ✅ |
| JSON/XML Support | ✴️ | ☑️ | ✴️ | ✅ |
| | | | | |
| **Developer Experience** | | | | |
| IDE Autocomplete | ✅ | ✅ | ✅ | ✅ |
| TypeScript Generation | ✅ | ✅ | ✅ | ✅ |
| Constructor Promotion | ✅ | ✅ | ✅ | ✅ |
| Property Attributes | ☑️ | ☑️ | ☑️ | ✅ |

**Legend:**

- ✅ Fully supported
- ☑️ Partially supported or optional
- ✴️ Requires #[ConverterMode] attribute
- ❌ Not supported

**Note:** UltraFast mode can be combined with #[ConverterMode] to enable JSON/XML support with minimal overhead (~1.3-1.5μs vs ~0.8μs array-only).
<!-- DTO_COMPARISON_END -->

## Available Attributes

| Attribute | LiteDto #[UltraFast] | LiteDto | SimpleDto #[UltraFast] | SimpleDto |
|-----------|----------------------|---------|------------------------|-----------|
| **Class Attributes** | | | | |
| #[UltraFast] | ✅ | ✅ | ✅ | ✅ |
| #[ConverterMode] | ✴️ | ✅ | ✴️ | ✅ |
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

**Legend:**

- ✅ Fully supported
- ✴️ Can be re-enabled with UltraFast parameters (e.g., `#[UltraFast(allowMapFrom: true)]`)
- ❌ Not supported

## Detailed Comparison

### Performance

**LiteDto** is optimized for speed:
- ~4.4μs average operation time
- **5.3x faster** than SimpleDto
- Minimal reflection overhead
- No validation or casting overhead

**SimpleDto** provides full features:
- ~18.4μs average operation time
- Includes validation and type casting
- Rich attribute system
- More overhead but more features
- Use `#[UltraFast]` attribute to skip validation/casting when not needed (~3.2μs, **5.8x faster**)

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

✅ **Type casting needed**
- DateTime conversion
- Enum mapping
- Custom type transformations

✅ **Advanced features required**
- Computed properties
- Lazy loading
- Conditional properties
- Hooks and events

✅ **Complex data structures**
- Nested validation
- Collection validation
- Cross-field validation

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

