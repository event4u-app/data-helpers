# Testing

This document describes the testing architecture, conventions, and commands for the data-helpers library.

See also: [`agents/quality-tools.md`](quality-tools.md) for quality tooling,
[`AGENTS.md`](../AGENTS.md) for the full project overview.

## Test Framework

**Pest** (PHPUnit 11 under the hood). All tests use Pest syntax — no PHPUnit test classes.

## Test Suites

Defined in `phpunit.xml`:

| Suite         | Location               | Purpose                                         |
|---------------|------------------------|-------------------------------------------------|
| Unit          | `tests/Unit/`          | Isolated class tests, no framework dependencies |
| Integration   | `tests/Integration/`   | Tests with framework integration (config, DTOs) |
| Documentation | `tests/Documentation/` | Validates code examples from docs and README    |

### E2E Tests (separate)

E2E tests live in `tests-e2e/` with isolated Laravel and Symfony applications:

| Directory            | Purpose                       |
|----------------------|-------------------------------|
| `tests-e2e/Laravel/` | Laravel E2E test app          |
| `tests-e2e/Symfony/` | Symfony E2E test app          |

E2E tests run in **isolated Docker containers** with specific framework versions.

## Test Commands

```bash
task test:run             # Run tests (default PHP 8.4 container)
task test:unit            # Unit tests only
task test:documentation   # Documentation tests only
task test:full            # Full suite including E2E
task test:e2e             # E2E tests only
task test:run PHP=8.2     # Tests with specific PHP version
```

### Framework-Specific E2E Tests

```bash
task test:laravel10       # Laravel 10 (isolated container)
task test:laravel11       # Laravel 11 (isolated container)
task test:symfony6        # Symfony 6 (isolated container)
task test:symfony7        # Symfony 7 (isolated container)
task test:doctrine2       # Doctrine 2 (isolated container)
task test:doctrine3       # Doctrine 3 (isolated container)
task test:matrix          # Complete test matrix (all combinations)
```

## Pest Configuration (`tests/Pest.php`)

- **Duplicate DTO check:** Runs before tests to catch naming conflicts in test DTOs
  (can be skipped via `SKIP_DUPLICATE_DTO_CHECK=true` env var).
- **MapperExceptions reset:** `beforeEach` and `afterEach` hooks reset `MapperExceptions`
  in `Fixtures`, `Integration`, and `Unit` suites for test isolation.
- **Integration Pest.php:** `tests/Integration/Pest.php` exists for integration-specific setup.
- **Custom expectation:** `toBeOne()` is registered as a custom expectation.

## Test Utilities (`tests/Utils/`)

Shared test DTOs, models, helpers, and fixtures live here:

| Directory             | Content                                    |
|-----------------------|--------------------------------------------|
| `tests/Utils/Dtos/`       | Shared DTOs for testing (DataMapper DTOs) |
| `tests/Utils/SimpleDtos/` | SimpleDto test classes                    |
| `tests/Utils/LiteDtos/`   | LiteDto test classes                      |
| `tests/Utils/Models/`     | Mock model classes (Eloquent-style)       |
| `tests/Utils/Entities/`   | Mock entity classes (Doctrine-style)      |
| `tests/Utils/Doctrine/`   | Doctrine-specific test utilities          |
| `tests/Utils/Filters/`    | Custom filter classes for testing         |
| `tests/Utils/Helpers/`    | Test helper classes (e.g., DuplicateDtoChecker) |
| `tests/Utils/Docu/`       | Documentation test helpers and DTOs       |
| `tests/Utils/XMLs/`       | XML test data and models                  |
| `tests/Utils/json/`       | JSON test fixtures                        |
| `tests/Utils/xml/`        | XML test fixtures                         |

Additional fixtures: `tests/Fixtures/` contains CSV, JSON, XML, and YAML test files.

## Test Support (`tests/Support/`)

Internal test support classes (e.g., cache adapters for testing).

## Writing Tests

### Conventions

- **File naming:** PascalCase + `Test` suffix (e.g., `DataAccessorTest.php`)
- **Test names:** Clear, human-readable descriptions (Pest `it()` or `test()` syntax)
- **No `namespace`** in Pest test files — they are global
- **No `use` statements for global PHP classes** — `DateTimeImmutable`, `Exception`, `stdClass`,
  etc. are already available in Pest test files without `namespace`
- **No `readonly` or `final`** on Pest test classes — these break Pest internals
- **Test DTOs/models** belong in `tests/Utils/`, not inline in test files
- **Test isolation:** Each test should be independent; use the `MapperExceptions::reset()`
  hooks already configured in `Pest.php`

### Example Test Structure

```php
// tests/Unit/DataAccessor/SomeFeatureTest.php

use event4u\DataHelpers\DataAccessor;

it('retrieves a nested value using dot notation', function(): void {
    $data = ['user' => ['name' => 'John']];

    $result = DataAccessor::get($data, 'user.name');

    expect($result)->toBe('John');
});
```

### Documentation Tests

Tests in `tests/Documentation/` validate that code examples in the README and Starlight
documentation actually work. When changing examples in docs, ensure these tests still pass:

```bash
task test:documentation
```

Key documentation test files:
- `ReadmeExamplesTest.php` — validates README code snippets
- `StarlightAllExamplesTest.php` — validates Starlight documentation examples
- `DataAccessorDocExamplesTest.php` — DataAccessor documentation examples
- `DataMapperDocExamplesTest.php` — DataMapper documentation examples
- `DataFilterDocExamplesTest.php` — DataFilter documentation examples

## Key Differences from Application Testing

This is a **library**, not an application. Therefore:

- **No database** — no migrations, no seeders, no database transactions
- **No HTTP requests** — no route testing, no request/response testing
- **No authentication** — no user login, no session handling
- **No snapshot testing** — not applicable for a utility library
- **No queues/jobs** — no async job testing
- **Framework features are mocked** — Laravel/Symfony features used in Integration tests
  are simulated without a full framework boot
- **E2E tests boot full frameworks** — only `tests-e2e/` apps boot actual Laravel/Symfony instances

## PHPUnit Configuration (`phpunit.xml`)

- Bootstrap: `tests/bootstrap.php`
- Memory limit: unlimited (`-1`)
- Error reporting: all (`-1`)
- Display errors: off (cleaner output)
- Cache directory: `.phpunit.cache/`

