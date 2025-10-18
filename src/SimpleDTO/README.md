# SimpleDTO Architecture

## Overview

The SimpleDTO package is designed with a modular, trait-based architecture to keep code organized, maintainable, and testable. Each trait has a single responsibility and is kept under 200 lines of code.

## Architecture Principles

1. **Separation of Concerns**: Each trait handles one specific aspect of DTO functionality
2. **Composition over Inheritance**: Traits are composed to build complete functionality
3. **Single Responsibility**: Each trait has one clear purpose
4. **Testability**: Small, focused traits are easier to test
5. **Maintainability**: Code is organized logically and easy to navigate

## Trait Structure

```
SimpleDTOTrait (Orchestrator)
├── SimpleDTOCastsTrait (Cast logic)
├── SimpleDTOValidationTrait (Validation logic) [PLANNED]
├── SimpleDTOMappingTrait (Property mapping) [PLANNED]
├── SimpleDTOVisibilityTrait (Hidden/visible properties) [PLANNED]
├── SimpleDTOComputedTrait (Computed properties) [PLANNED]
└── SimpleDTOSerializationTrait (Serialization logic) [PLANNED]
```

## Current Implementation

### SimpleDTOTrait (Orchestrator)

**Location**: `src/SimpleDTO/SimpleDTOTrait.php`

**Responsibilities**:
- Orchestrate all DTO functionality
- Provide core methods: `toArray()`, `jsonSerialize()`, `fromArray()`
- Compose specialized traits
- Coordinate between traits

**Methods**:
- `toArray(): array` - Convert DTO to array
- `jsonSerialize(): array` - Serialize DTO to JSON
- `fromArray(array $data): static` - Create DTO from array

**Size**: ~90 lines

---

### SimpleDTOCastsTrait (Cast Logic)

**Location**: `src/SimpleDTO/SimpleDTOCastsTrait.php`

**Responsibilities**:
- Define available casts via `casts()` method
- Apply casts to data arrays
- Resolve and cache cast instances
- Parse cast strings with parameters
- Map built-in cast aliases to classes

**Methods**:
- `casts(): array` - Define casts for DTO (override in child classes)
- `getCasts(): array` - Get casts using reflection
- `applyCasts(array $data, array $casts): array` - Apply all casts
- `castAttribute(string $key, mixed $value, string $cast, array $attributes): mixed` - Cast single attribute
- `resolveBuiltInCast(string $cast): string` - Resolve cast aliases
- `parseCast(string $cast): array` - Parse cast parameters
- `resolveCaster(string $castClass, array $parameters): object` - Get/create cast instance
- `clearCastCache(): void` - Clear cast cache (for testing)
- `getBuiltInCasts(): array` - Get all built-in casts

**Built-in Casts**:
- `array` → `ArrayCast::class`
- `boolean` / `bool` → `BooleanCast::class`
- `datetime` → `DateTimeCast::class`
- `integer` / `int` → `IntegerCast::class`
- `float` / `double` → `FloatCast::class`
- `string` → `StringCast::class`
- `decimal` → `DecimalCast::class`
- `json` → `JsonCast::class`

**Size**: ~240 lines

---

## Planned Traits

### SimpleDTOValidationTrait (Validation Logic)

**Planned Location**: `src/SimpleDTO/SimpleDTOValidationTrait.php`

**Responsibilities**:
- Auto-infer validation rules from types
- Process validation attributes
- Validate data before DTO creation
- Provide validation methods

**Planned Methods**:
- `rules(): array` - Get validation rules
- `validateAndCreate(array $data): static` - Validate and create
- `validate(array $data): array` - Validate data
- `messages(): array` - Custom error messages
- `attributes(): array` - Custom attribute names

**Target Size**: < 200 lines

---

### SimpleDTOMappingTrait (Property Mapping)

**Planned Location**: `src/SimpleDTO/SimpleDTOMappingTrait.php`

**Responsibilities**:
- Process MapFrom/MapTo attributes
- Apply property name transformers
- Handle bidirectional mapping
- Support dot notation

**Planned Methods**:
- `getInputMapping(): array` - Get input property mapping
- `getOutputMapping(): array` - Get output property mapping
- `applyInputMapping(array $data): array` - Apply input mapping
- `applyOutputMapping(array $data): array` - Apply output mapping

**Target Size**: < 200 lines

---

### SimpleDTOVisibilityTrait (Visibility Logic)

**Planned Location**: `src/SimpleDTO/SimpleDTOVisibilityTrait.php`

**Responsibilities**:
- Process Hidden/Visible attributes
- Handle conditional visibility
- Implement only()/except() methods
- Filter properties for serialization

**Planned Methods**:
- `only(array $properties): self` - Include only specified properties
- `except(array $properties): self` - Exclude specified properties
- `getVisibleProperties(): array` - Get visible properties
- `isPropertyVisible(string $property): bool` - Check visibility

**Target Size**: < 200 lines

---

### SimpleDTOComputedTrait (Computed Properties)

**Planned Location**: `src/SimpleDTO/SimpleDTOComputedTrait.php`

**Responsibilities**:
- Process Computed attributes
- Cache computed values
- Handle lazy computed properties
- Track dependencies

**Planned Methods**:
- `getComputedProperties(): array` - Get all computed properties
- `computeProperty(string $property): mixed` - Compute single property
- `clearComputedCache(): void` - Clear computed cache

**Target Size**: < 200 lines

---

### SimpleDTOSerializationTrait (Serialization Logic)

**Planned Location**: `src/SimpleDTO/SimpleDTOSerializationTrait.php`

**Responsibilities**:
- Handle different serialization formats
- Implement wrapping
- Custom serializers
- Partial serialization

**Planned Methods**:
- `toJson(): string` - Serialize to JSON string
- `toXml(): string` - Serialize to XML
- `toYaml(): string` - Serialize to YAML
- `wrap(string $key): self` - Wrap in key

**Target Size**: < 200 lines

---

## Cast Classes

All cast classes implement the `CastsAttributes` interface and are located in `src/SimpleDTO/Casts/`.

### Implemented Casts

1. **ArrayCast** - JSON strings to arrays
2. **BooleanCast** - Various formats to boolean
3. **DateTimeCast** - Strings/timestamps to DateTimeImmutable
4. **DecimalCast** - Numbers to decimal strings with precision
5. **FloatCast** - Strings/integers to float
6. **IntegerCast** - Strings/floats to integer
7. **JsonCast** - JSON strings to arrays/objects
8. **StringCast** - Various types to string

### Planned Casts

1. **EnumCast** - Strings/integers to PHP 8.1+ enums
2. **CollectionCast** - Arrays to Laravel Collections
3. **EncryptedCast** - Encrypted values
4. **TimestampCast** - DateTimeInterface to Unix timestamp
5. **HashedCast** - One-way hashing for passwords

---

## Design Decisions

### Why Traits?

1. **Flexibility**: Traits can be mixed and matched
2. **Reusability**: Traits can be used in different contexts
3. **Testing**: Small traits are easier to test in isolation
4. **Maintainability**: Clear separation of concerns
5. **Performance**: No runtime overhead compared to inheritance

### Why Separate Cast Logic?

1. **Size**: Cast logic is substantial (~240 lines)
2. **Complexity**: Cast resolution, caching, and parsing is complex
3. **Testability**: Easier to test cast logic in isolation
4. **Reusability**: Cast logic could be used outside DTOs
5. **Clarity**: Clear separation between orchestration and casting

### Why Keep SimpleDTOTrait Small?

1. **Readability**: Easy to understand at a glance
2. **Maintainability**: Less code to maintain
3. **Extensibility**: Easy to add new traits
4. **Testing**: Simpler to test orchestration logic
5. **Documentation**: Easier to document

---

## Development Guidelines

### Adding New Traits

1. Create trait in `src/SimpleDTO/`
2. Keep trait under 200 lines
3. Single responsibility only
4. Add comprehensive tests
5. Update this README
6. Update `SimpleDTOTrait` to use new trait

### Adding New Casts

1. Create cast class in `src/SimpleDTO/Casts/`
2. Implement `CastsAttributes` interface
3. Add to `SimpleDTOCastsTrait::resolveBuiltInCast()` if built-in
4. Add comprehensive tests
5. Update documentation
6. Add example usage

### Testing Guidelines

1. Unit tests for each trait
2. Integration tests for trait combinations
3. Performance tests for critical paths
4. Minimum 90% code coverage
5. Test edge cases and error conditions

---

## Performance Considerations

### Cast Caching

Cast instances are cached to avoid repeated instantiation:

```php
private static array $castCache = [];
```

Cache key format: `{ClassName}:{param1},{param2}`

### Reflection Caching

Reflection results should be cached in future traits to avoid repeated reflection calls.

### Property Access

Direct property access is used instead of magic methods for maximum performance.

---

## Future Enhancements

See `dto-roadmap.txt` in the package root for the complete roadmap.

**Next Priorities**:
1. Validation System (Phase 2)
2. Property Mapping (Phase 3)
3. Enum Cast (Phase 4.1)
4. Hidden Properties (Phase 5)
5. Artisan Commands (Phase 10.1)

---

## Contributing

When contributing to SimpleDTO:

1. Follow the trait structure
2. Keep traits under 200 lines
3. Add comprehensive tests
4. Update documentation
5. Follow PSR-12 coding standards
6. Use ECS and PHPStan Level 9

---

## Questions?

For questions about the architecture or implementation:
- Check `dto-roadmap.txt` for the complete plan
- Review existing traits for patterns
- Create an issue on GitHub
- Contact: matze4u

