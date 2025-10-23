# Data Helpers Examples

This directory contains working examples demonstrating all features of the Data Helpers library. All examples are fully tested and can be run directly.

## Directory Structure

The examples are organized to match the documentation structure:

```
examples/
├── main-classes/          # Core classes
│   ├── data-accessor/     # DataAccessor examples
│   ├── data-mutator/      # DataMutator examples
│   ├── data-mapper/       # DataMapper examples
│   └── data-filter/       # DataFilter examples
├── simple-dto/            # SimpleDTO examples
│   ├── creating-dtos/     # DTO creation
│   ├── type-casting/      # Type casting
│   ├── validation/        # Validation
│   ├── property-mapping/  # Property mapping
│   ├── serialization/     # Serialization
│   ├── conditional-properties/  # Conditional properties
│   ├── lazy-properties/   # Lazy loading
│   ├── computed-properties/  # Computed properties
│   ├── collections/       # Collections
│   ├── security-visibility/  # Security & visibility
│   ├── typescript-generation/  # TypeScript generation
│   └── ide-support/       # IDE support
├── framework-integration/ # Framework integrations
│   ├── laravel/           # Laravel examples
│   ├── symfony/           # Symfony examples
│   └── doctrine/          # Doctrine examples
├── advanced/              # Advanced features
│   ├── pipelines/         # Pipeline examples
│   ├── template-expressions/  # Template expressions
│   ├── reverse-mapping/   # Reverse mapping
│   ├── hooks-events/      # Hooks & events
│   ├── callback-filters/  # Callback filters
│   ├── query-builder/     # Query builder
│   └── group-by/          # GROUP BY operations
├── performance/           # Performance examples
│   ├── optimization/      # Optimization techniques
│   └── benchmarks/        # Benchmarking
├── real-world/            # Real-world examples
├── troubleshooting/       # Troubleshooting examples
├── core-concepts/         # Core concept examples
│   └── wildcards/         # Wildcard examples
└── attributes/            # Attribute examples
```

## Running Examples

All examples can be run directly from the command line:

```bash
# Run a specific example
php examples/main-classes/data-accessor/basic-usage.php

# Run SimpleDTO examples
php examples/simple-dto/creating-dtos/basic-dto.php
php examples/simple-dto/type-casting/basic-casts.php

# Run framework integration examples
php examples/framework-integration/laravel/basic-usage.php

# Run real-world examples
php examples/real-world/ecommerce.php
```

## Example Categories

### Main Classes

**DataAccessor** - Read nested data with dot-notation and wildcards
- `basic-usage.php` - Complete example showing dot-notation, wildcards, and default values

**DataMutator** - Modify nested data structures safely
- `basic-usage.php` - Set, merge, and unset operations with wildcards

**DataMapper** - Transform data structures with templates and pipelines
- `simple-mapping.php` - Basic template-based mapping
- `with-hooks.php` - Using hooks for custom logic
- `pipeline.php` - Filter pipelines and transformations
- `mapped-data-model.php` - Using MappedDataModel class
- `template-expressions.php` - Advanced template syntax
- `reverse-mapping.php` - Bidirectional mapping
- `dto-integration.php` - Integration with SimpleDTO

**DataFilter** - Query and filter data with SQL-like API
- `basic-usage.php` - WHERE, ORDER BY, LIMIT operations
- `wildcard-where.php` - Filtering with wildcards
- `custom-wildcard-operators.php` - Creating custom operators
- `distinct-like-operators.php` - DISTINCT and LIKE operations
- `group-by-aggregations.php` - Grouping and aggregating data
- `query-builder.php` - Fluent query builder API
- `callback-filters.php` - Custom filter callbacks
- `custom-operators.php` - Extending with custom operators
- `complex-queries.php` - Advanced query scenarios

### SimpleDTO

**Creating DTOs**
- `basic-dto.php` - Simple DTO with required properties
- `dto-factory.php` - Factory pattern for DTOs
- `wrapping.php` - Wrapping existing data
- `optional-properties.php` - Handling optional properties

**Type Casting**
- `basic-casts.php` - Common type casts
- `all-casts.php` - Complete cast overview
- `enum-cast.php` - Enum casting
- `collection-cast.php` - Collection casting
- `timestamp-cast.php` - Date/time casting
- `hashed-cast.php` - Password hashing
- `encrypted-cast.php` - Data encryption
- `lazy-cast.php` - Lazy loading casts

**Validation**
- `basic-validation.php` - Simple validation rules
- `advanced-validation.php` - Complex validation scenarios
- `request-validation-core.php` - Core request validation
- `request-validation-laravel.php` - Laravel integration
- `request-validation-symfony.php` - Symfony integration
- `validation-modes.php` - Different validation modes
- `nested-validation.php` - Validating nested DTOs
- `html-error-responses.php` - HTML error responses
- `advanced-validation-attributes.php` - Advanced validation attributes
- `laravel-validation-integration.php` - Laravel validation integration
- `symfony-validation-integration.php` - Symfony validation integration
- `advanced-rule-combinations.php` - Advanced rule combinations

**Conditional Properties**
- `basic-conditional.php` - Simple conditional properties
- `whencallback-with-parameters.php` - Callbacks with parameters
- `with-method.php` - Using with() method
- `context-based-conditions.php` - Context-aware conditions
- `custom-conditions.php` - Creating custom conditions
- `laravel-conditional-attributes.php` - Laravel-specific attributes
- `symfony-conditional-attributes.php` - Symfony-specific attributes

**Lazy Properties**
- `basic-lazy.php` - Simple lazy properties
- `lazy-union-types.php` - Lazy with union types
- `optional-lazy-combinations.php` - Combining optional and lazy

**Computed Properties**
- `basic-computed.php` - Simple computed properties

**Collections**
- `data-collection.php` - Working with collections
- `dto-sorting.php` - Sorting DTOs in collections

**Security & Visibility**
- `visibility-hidden.php` - Hiding properties
- `visibility-context.php` - Context-based visibility
- `visibility-explained.php` - Detailed explanation
- `visibility-real-world.php` - Practical use case
- `visibility-static-provider.php` - Static visibility provider

**TypeScript Generation**
- `basic-generation.php` - Generate TypeScript types
- `generator-options.php` - Customizing generation

**IDE Support**
- `better-error-messages.php` - Better error messages
- `ide-support.php` - IDE support features

**Serialization**
- `serializers.php` - Serialization examples
- `transformers.php` - Data transformation
- `normalizers.php` - Data normalization
- `serializer-options.php` - Customizing serialization

**Property Mapping**
- `basic-mapping.php` - Property name mapping

### Framework Integration

**Laravel**
- `basic-usage.php` - Laravel integration basics
- `eloquent-integration.php` - Working with Eloquent models

**Symfony**
- `symfony-doctrine.php` - Symfony with Doctrine

**Doctrine**
- `doctrine-integration.php` - Working with Doctrine entities

### Advanced Features

**Pipelines**
- `basic-pipeline.php` - Pipeline examples

**Template Expressions**
- `template-expressions.php` - Advanced template syntax

**Reverse Mapping**
- `reverse-mapping.php` - Bidirectional mapping

**Hooks & Events**
- `with-hooks.php` - Using hooks for custom logic

**Callback Filters**
- `callback-filters.php` - Custom filter callbacks

**Query Builder**
- `query-builder.php` - Fluent query builder API

**GROUP BY**
- `group-by-aggregations.php` - Grouping and aggregating data

### Performance

**Optimization**
- `performance.php` - Performance optimization techniques
- `optimized-reflection.php` - Optimized reflection usage

**Benchmarks**
- `benchmarking.php` - Benchmarking examples
- `performance-testing.php` - Performance testing

### Real-World Examples

- `ecommerce.php` - E-commerce application example
- `blog.php` - Blog application example
- `api-resources-complete.php` - Complete API resource example
- `form-requests-complete.php` - Complete form request example
- `advanced-features-showcase.php` - Showcase of advanced features
- `api-integration.php` - API integration example
- `partial-updates.php` - Partial update example

### Troubleshooting

- `exception-handling.php` - Exception handling examples

### Core Concepts

**Wildcards**
- `wildcard-where.php` - Wildcard examples

### Attributes

- `naming-convention-enum.php` - Naming convention enum
- `comparison-operator-enum.php` - Comparison operator enum

## Testing

All examples are tested automatically. See the test files in `tests/Documentation/ExamplesTest.php`.

## Documentation

Each example is referenced in the corresponding documentation page. See the [documentation](https://data-helpers.event4u.app) for detailed explanations.

## Contributing

When adding new examples:

1. Place them in the appropriate directory
2. Use descriptive filenames (no numbers)
3. Add comprehensive comments
4. Update this README
5. Add a link in the corresponding documentation page
6. Add tests in `tests/Documentation/ExamplesTest.php`

