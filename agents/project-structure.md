# Project Structure

This document describes the architecture and directory layout of the data-helpers library.

See also: [`AGENTS.md`](../AGENTS.md) for the full project overview,
[`agents/testing.md`](testing.md) for test architecture.

## Overview

`event4u/data-helpers` is a **framework-agnostic PHP library** with zero required dependencies.
It provides data mapping, DTOs, filtering, collections, and utility helpers. Framework integrations
(Laravel, Symfony, Doctrine) are optional and loaded only when the framework is present.

**Namespace:** `event4u\DataHelpers\`

## Core Classes (`src/`)

### Data Processing

| Class / File            | Purpose                                          |
|-------------------------|--------------------------------------------------|
| `DataAccessor.php`      | Read & transform nested data via dot notation    |
| `DataMapper.php`        | Template-based data mapping between structures   |
| `DataMutator.php`       | Modify nested data structures                    |
| `DataFilter.php`        | SQL-like data filtering with operators            |
| `DataFilterWrapper.php` | Wrapper for fluent filter chains                 |
| `DataCollection.php`    | Type-safe collections with filtering/sorting     |
| `MappedDataModel.php`   | Data model with built-in mapping support         |
| `DataHelpersConfig.php` | Library configuration class                      |

### DataMapper Internals (`src/DataMapper/`)

The DataMapper is the most complex component. Internal structure:

| Directory / File      | Purpose                                    |
|-----------------------|--------------------------------------------|
| `Context/`            | Mapping context and state management       |
| `Hook/`               | Hook system for mapping lifecycle events   |
| `Pipeline/`           | Pipeline processing for mapping steps      |
| `Support/`            | Internal mapper support classes            |
| `Template/`           | Mapping template parsing and handling      |
| `FluentDataMapper.php`| Fluent API for building mappings           |
| `MapperExceptions.php`| Exception handling configuration           |
| `MappingOptions.php`  | Mapping options and configuration          |

### DataFilter Internals (`src/DataFilter/`)

| Directory    | Purpose                              |
|-------------|--------------------------------------|
| `Operators/` | Filter operator implementations     |

### SimpleDto System (`src/SimpleDto/`)

Full-featured immutable DTO system with validation, casting, serialization:

| Directory / File   | Purpose                                       |
|--------------------|-----------------------------------------------|
| `Attributes/`      | PHP attributes for DTO configuration          |
| `Casters/`         | Type casting implementations                  |
| `Casts/`           | Cast definitions                              |
| `Concerns/`        | Shared concerns (traits used internally)      |
| `Config/`          | DTO configuration                             |
| `Contracts/`       | Interfaces and contracts                      |
| `Enums/`           | DTO-specific enums                            |
| `Normalizers/`     | Data normalization before DTO creation        |
| `Pipeline/`        | DTO creation pipeline                         |
| `Serializers/`     | Serialization formats (JSON, array, etc.)     |
| `Support/`         | Internal support classes                      |
| `Transformers/`    | Data transformation during DTO creation       |
| `SimpleDto.php`    | Main SimpleDto class                          |
| `ImmutableSimpleDto.php` | Immutable variant                       |
| `DtoCollection.php`| Typed DTO collections                         |
| `DtoFactory.php`   | Factory for creating DTOs                     |
| `DtoInterface.php` | DTO contract                                  |
| `SimpleDto*Trait.php` | Feature traits (20+ traits for modular features) |

### LiteDto System (`src/LiteDto/`)

Ultra-fast minimalistic DTOs — fewer features, better performance:

| Directory / File   | Purpose                              |
|--------------------|--------------------------------------|
| `Attributes/`      | LiteDto-specific attributes          |
| `Casters/`         | LiteDto type casters                 |
| `Contracts/`       | LiteDto interfaces                   |
| `Support/`         | Internal support                     |
| `LiteDto.php`      | Main LiteDto class                   |
| `ImmutableLiteDto.php` | Immutable variant                |
| `LiteDto*Trait.php`| Feature traits (Eloquent, Doctrine, Object) |

### Converters (`src/Converters/`)

Format converters implementing `ConverterInterface`:

`JsonConverter`, `XmlConverter`, `YamlConverter`, `CsvConverter`

### Validation (`src/Validation/`)

Framework-agnostic validation engine:

`Validator.php`, `ValidationResult.php`, `HtmlErrorFormatter.php`

### Helpers (`src/Helpers/`)

Utility helpers with `Helper` suffix:

| Helper            | Purpose                                    |
|-------------------|--------------------------------------------|
| `MathHelper`      | Math operations with precision handling    |
| `EnvHelper`       | Environment variable access & type casting |
| `ConfigHelper`    | Configuration file loading                 |
| `DotPathHelper`   | Dot notation path operations               |
| `ObjectHelper`    | Object inspection and manipulation         |

### Framework Integrations (`src/Frameworks/`)

Optional framework-specific code — loaded only when the framework is present:

| Directory              | Purpose                                  |
|------------------------|------------------------------------------|
| `Frameworks/Laravel/`  | Service provider, casts, commands, traits|
| `Frameworks/Symfony/`  | Bundle, value resolvers, DI config       |

### Other Directories

| Directory / File     | Purpose                                      |
|----------------------|----------------------------------------------|
| `Config/`            | Configuration loading (`ConfigLoader.php`)   |
| `Console/`           | CLI commands (`WarmCacheCommand.php`)        |
| `Enums/`             | Shared enums (`CacheDriver`, `Mode`, etc.)   |
| `Exceptions/`        | Custom exception classes                     |
| `Logging/`           | Logging infrastructure and drivers           |
| `Traits/`            | Shared traits (`DtoMappingTrait`, etc.)      |
| `Support/`           | Internal support classes (**ECS/Rector skip**)|

## Support Directory Warning

`src/Support/` is **intentionally skipped** by both ECS and Rector. It uses fully qualified
names (FQN) and has special patterns. **Do NOT modify** these files with code style tools.
**Do NOT add `use` imports** to Support files unless explicitly required.

## Project Root Files

| File / Directory       | Purpose                                 |
|------------------------|-----------------------------------------|
| `composer.json`        | Package definition and dependencies     |
| `phpunit.xml`          | PHPUnit/Pest test configuration         |
| `phpstan.neon`         | PHPStan static analysis configuration   |
| `ecs.php`              | ECS code style configuration            |
| `rector.php`           | Rector refactoring configuration        |
| `Taskfile.yml`         | Task runner main file                   |
| `taskfiles/`           | Task runner sub-files (tests, quality, etc.) |
| `docker-compose.yml`   | Docker container definitions            |
| `docker/`              | Docker configuration files              |
| `config/`              | Library default configuration           |
| `types/`               | PHPStan type stubs                      |
| `benchmarks/`          | PHPBench benchmarks                     |
| `examples/`            | Runnable code examples                  |
| `scripts/`             | Build and utility scripts               |
| `starlight/`           | Starlight documentation site (Astro)    |
| `tests-e2e/`           | E2E framework test applications         |
| `recipe/`              | Symfony Flex recipe                     |
| `dist/`                | Built documentation output              |
| `storage/`             | Cache and temporary storage             |

