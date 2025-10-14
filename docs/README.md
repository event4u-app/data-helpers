# Data Handling Helpers

This section documents the lightweight data-handling helpers used across the codebase:

- Data Accessor — read values from nested structures with dot-paths and wildcards
- Data Mutator — write/merge/unset values using dot-paths and wildcards
- Data Mapper — map values between structures, including template-based mapping
- Dot Path — the path syntax and wildcard semantics shared by all helpers

All helpers live under the `App\Helpers` namespace and are framework-friendly (arrays, DTOs, Eloquent models, and Laravel collections).

## Quick links

### Core Features
- Accessor: [data-accessor.md](data-accessor.md)
- Mutator: [data-mutator.md](data-mutator.md)
- Mapper:  [data-mapper.md](data-mapper.md)
- Dot Path syntax: [dot-path.md](dot-path.md)

### Advanced Features
- Configuration: [configuration.md](configuration.md)
- Exception Handling: [exception-handling.md](exception-handling.md)
- Template Expressions: [template-expressions.md](template-expressions.md)
- Pipeline API: [data-mapper-pipeline.md](data-mapper-pipeline.md)
- Filters: [filters.md](filters.md)
- MappedDataModel: [mapped-data-model.md](mapped-data-model.md)
- Enum Support: [enum-support.md](enum-support.md)

### Integration & Performance
- Framework Integration: [framework-integration.md](framework-integration.md)
- Symfony Recipe: [symfony-recipe.md](symfony-recipe.md)
- Examples: [examples.md](examples.md)
- Benchmarks: [benchmarks.md](benchmarks.md)

## At a glance

- Dot paths like `user.profile.name`
- Wildcards with `*`, including deep wildcards like `users.*.profile.*.city`
- Arrays, objects/DTOs, Laravel Models, Collections supported
- Consistent null handling and wildcard behavior across helpers

### Data Mapper modes

- Normal mapping (dot-path → dot-path), incl. wildcards and per-entry overrides
- Structured mapping (source/target entries and mapping pairs)
- Template-based mapping from named sources (build structures)
- Inverse template mapping to named targets (apply values into DTOs/Models/arrays)
- Auto-map by matching names (shallow and deep modes)

## Conventions

- Null handling: by default helpers avoid throwing on missing data and either return `null` (Accessor) or skip writes (Mapper with
  `skipNull=true`).
- Wildcards: Accessor returns associative arrays keyed by resolved dot-paths; Mapper can use those results to expand targets.
- Performance: Helpers are designed for clarity and testability; prefer batching (Mapper / mapMany) for larger transformations.

## Performance Features

The package includes lightweight in-method caching for optimal performance:

- **Template Expression Cache** - Parsed expressions are cached using simple static arrays
- **Filter Parsing Cache** - Filter arguments are cached to avoid repeated parsing
- **Reflection Cache** - ReflectionClass instances are cached (standard PHP best practice)
- **Path Compilation Cache** - Dot-notation paths are pre-compiled and cached
- **Framework Detection Cache** - Framework detection is cached (one-time check)

**Performance Modes:**
- **Fast Mode** (default): ~2x faster parsing, no escape sequence handling
- **Safe Mode**: Full escape sequence handling for special cases

See [configuration.md](configuration.md) for details on configuring performance mode.
